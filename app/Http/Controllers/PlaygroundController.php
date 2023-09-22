<?php

namespace App\Http\Controllers;

use App\Models\NordigenAgreement;
use App\Models\NordigenRequisition;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nordigen\NordigenPHP\API\NordigenClient;

class PlaygroundController extends Controller
{
    const SANDBOX_INSTITUTION = 'SANDBOXFINANCE_SFIN0000';

    const REQUISITION_STATUS_LINKED = 'LN';

    const NORDIGEN_ACCESS_TOKEN = 'nordigen.access_token';

    const NORDIGEN_REFRESH_TOKEN = 'nordigen.refresh_token';

    public function createRequisition()
    {
        $client = $this->getNordigenClient();

        $institutionId = self::SANDBOX_INSTITUTION;

        // Create the end user agreement
        $agreementData = $client->endUserAgreement->createEndUserAgreement($institutionId);

        DB::beginTransaction();

        $agreement = NordigenAgreement::create([
            'nordigen_id' => $agreementData['id'],
            'institution_id' => $agreementData['institution_id'],
            'nordigen_created_at' => $agreementData['created'],
        ]);

        // Create the requisition
        $redirectUrl = config('app.url').'/nordigen/callback';
        $agreementId = $agreement->nordigen_id;

        $requisition = $agreement->requisition()->create();
        $requisitionReference = $requisition->uuid;

        $requisitionData = $client->requisition->createRequisition(
            $redirectUrl,
            $institutionId,
            $agreementId,
            $requisitionReference
        );

        $requisition->update([
            'nordigen_id' => $requisitionData['id'],
            'link' => $requisitionData['link'],
            'nordigen_created_at' => $requisitionData['created'],
        ]);

        DB::commit();

        return $requisition->link;
    }

    public function handleRequisition(NordigenRequisition $requisition)
    {
        // Prevent the agreement from being saved to the database more than once
        if ($requisition->agreement->isLocallySaved()) {
            Log::info('Agreement already in the database', ['requisitionId' => $requisition->id]);

            return response()->noContent();
        }

        $client = $this->getNordigenClient();

        // Get the requisition data from Nordigen
        $requisitionData = $client->requisition->getRequisition($requisition->nordigen_id);

        // Check if the requisition is at the state we expect
        $status = $requisitionData['status'];
        if ($status !== self::REQUISITION_STATUS_LINKED) {
            throw new Exception("Account(s) not linked, got status $status");
        }

        // Check if the agreement ID is correct
        $agreementId = $requisition->agreement->nordigen_id;
        if ($requisitionData['agreement'] !== $agreementId) {
            throw new Exception("Agreement ID doesn't match");
        }

        // Get the agreement data from Nordigen
        $agreementData = $client->endUserAgreement->getEndUserAgreement($agreementId);

        DB::beginTransaction();

        // Update the agreement
        // Note: update() can't be used here since "accepted_at" needs to be set for the mutator of "access_valid_for_days" to work, since it depends on "accepted_at"
        $requisition->agreement->accepted_at = $agreementData['accepted'];
        $requisition->agreement->access_valid_for_days = $agreementData['access_valid_for_days'];
        $requisition->agreement->save();

        // Create the accounts
        // TODO Change this to: many account have many requisitions. This way, the user can modify the account details. Use the Nordigen ID to make sure we're dealing with the same account
        foreach ($requisitionData['accounts'] as $accountId) {
            $accountData = $client->account($accountId)->getAccountDetails()['account'];
            $requisition->accounts()->create([
                'nordigen_id' => $accountId,
                'currency' => $accountData['currency'],
                'iban' => $accountData['iban'] ?? null,
                'name' => $accountData['name'] ?? null,
            ]);
        }

        DB::commit();

        $requisition->load('accounts');

        return response($requisition, Response::HTTP_CREATED);
    }

    public function getNordigenClient(): NordigenClient
    {
        $client = new NordigenClient(config('services.nordigen.id'), config('services.nordigen.key'));

        // Use the cached access token, if available
        if (Cache::has(self::NORDIGEN_ACCESS_TOKEN)) {
            Log::debug('Nordigen access token: HIT');
            $accessToken = Cache::get(self::NORDIGEN_ACCESS_TOKEN);
            $client->setAccessToken($accessToken);

            return $client;
        }

        Log::debug('Nordigen access token: MISS');

        // If the access token is not available, try to use the refresh token to get a new one
        if (Cache::has(self::NORDIGEN_REFRESH_TOKEN)) {
            $refreshToken = Cache::get(self::NORDIGEN_REFRESH_TOKEN);
            Log::debug('Nordigen refresh token: HIT');
            $response = $client->refreshAccessToken($refreshToken);

            // Cache the new access token
            $accessToken = $response['access'];
            $accessExpires = $response['access_expires'] - 1000;
            Cache::set(self::NORDIGEN_ACCESS_TOKEN, $accessToken, $accessExpires);
            Log::debug("Nordigen access token: SET $accessExpires");

            return $client;
        }

        Log::debug('Nordigen refresh token: MISS');

        // If no refresh token is available, create a brand-new access token and cache it
        $response = $client->createAccessToken();

        // Cache the new access token
        $accessToken = $response['access'];
        $accessExpires = $response['access_expires'] - 1000;
        Cache::set(self::NORDIGEN_ACCESS_TOKEN, $accessToken, $accessExpires);
        Log::debug("Nordigen access token: SET $accessExpires");

        // Cache the new response token
        $refreshToken = $response['refresh'];
        $refreshExpires = $response['refresh_expires'] - 1000;
        Cache::set(self::NORDIGEN_REFRESH_TOKEN, $refreshToken, $refreshExpires);
        Log::debug("Nordigen refresh token: SET $refreshExpires");

        return $client;
    }
}

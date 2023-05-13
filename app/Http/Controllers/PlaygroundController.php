<?php

namespace App\Http\Controllers;

use App\Models\NordigenAgreement;
use App\Models\NordigenRequisition;
use Exception;
use Illuminate\Support\Facades\DB;
use Nordigen\NordigenPHP\API\NordigenClient;

class PlaygroundController extends Controller
{
    const SANDBOX_INSTITUTION = 'SANDBOXFINANCE_SFIN0000';

    const REQUISITION_STATUS_LINKED = 'LN';

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

        // TODO Check if the agreement is already saved; if so, skip

        // Get the agreement data from Nordigen
        $agreementData = $client->endUserAgreement->getEndUserAgreement($agreementId);

        DB::beginTransaction();

        // Update the agreement
        // Note: update() can't be used here since "accepted_at" needs to be set for the mutator of "access_valid_for_days" to work, since it depends on "accepted_at"
        $requisition->agreement->accepted_at = $agreementData['accepted'];
        $requisition->agreement->access_valid_for_days = $agreementData['access_valid_for_days'];

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

        return [$requisition];
    }

    public function getNordigenClient(): NordigenClient
    {
        $client = new NordigenClient(config('services.nordigen.id'), config('services.nordigen.key'));

        // TODO Cache the access and refresh token for later use
        $client->createAccessToken();

        return $client;
    }
}

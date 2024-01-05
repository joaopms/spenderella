<?php

namespace App\Http\Controllers;

use App\Integrations\Nordigen\NordigenClient;
use App\Models\NordigenRequisition;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaygroundController extends Controller
{
    const REQUISITION_STATUS_LINKED = 'LN';

    public function createRequisition()
    {
        $client = new NordigenClient();

        return $client->newRequisition()->link;
    }

    public function handleRequisition(NordigenRequisition $requisition)
    {
        // Prevent the agreement from being saved to the database more than once
        if ($requisition->agreement->isLocallySaved()) {
            Log::info('Agreement already in the database', ['requisitionId' => $requisition->id]);

            return response()->noContent();
        }

        $client = new NordigenClient();

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
}

<?php

namespace App\Services;

use App\Exceptions\SpenderellaNordigenException;
use App\Integrations\Nordigen\NordigenClient;
use App\Models\NordigenAccount;
use App\Models\NordigenAgreement;
use App\Models\NordigenRequisition;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NordigenService
{
    const REQUISITION_STATUS_LINKED = 'LN';

    public function __construct(protected NordigenClient $client)
    {
    }

    /**
     * Creates a Nordigen end user agreement and save it to the database
     */
    public function createEndUserAgreement(string $institutionId): NordigenAgreement
    {
        // Create the end user agreement on Nordigen's side
        $agreementData = $this->client->endUserAgreementCreate($institutionId);

        $agreement = NordigenAgreement::create([
            'nordigen_id' => $agreementData['id'],
            'institution_id' => $agreementData['institution_id'],
            'nordigen_created_at' => $agreementData['created'],
        ]);

        return $agreement;
    }

    /**
     * Creates a Nordigen requisition and saves it to the database
     */
    public function newRequisition(string $institutionId): NordigenRequisition
    {
        DB::beginTransaction();

        // Create the end user agreement
        $agreement = $this->createEndUserAgreement($institutionId);
        $agreement->save(); // Saving so we can use the ID when creating the requisition

        // Create the requisition
        $requisition = $agreement->requisition()->create();

        // Create the requisition on Nordigen's side
        $redirectUrl = route('nordigen.callback');
        $requisitionData = $this->client->requisitionCreate(
            $redirectUrl,
            $institutionId,
            $agreement->nordigen_id,
            $requisition->uuid // Used as the requisition reference
        );

        // Update the requisition with Nordigen's data
        $requisition->update([
            'nordigen_id' => $requisitionData['id'],
            'link' => $requisitionData['link'],
            'nordigen_created_at' => $requisitionData['created'],
        ]);

        DB::commit();

        return $requisition;
    }

    /**
     * Tries to update our local Agreement after the user consents access to their data
     *
     *
     * @return bool Returns `false` if the agreement was already updated
     *
     * @throws Exception
     */
    public function updateAfterUserConsent(NordigenRequisition $requisition): bool
    {
        // Prevent the agreement from being saved to the database more than once
        if ($requisition->agreement->isLocallySaved()) {
            Log::info('Agreement already in the database', ['requisitionId' => $requisition->id]);

            return false;
        }

        // Get the requisition data from Nordigen
        $requisitionData = $this->client->requisitionGet($requisition->nordigen_id);

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
        $agreementData = $this->client->getEndUserAgreement($agreementId);

        DB::beginTransaction();

        // Update the agreement
        // Note: update() can't be used here since "accepted_at" needs to be set for the mutator of "access_valid_for_days" to work, since it depends on "accepted_at"
        $requisition->agreement->accepted_at = $agreementData['accepted'];
        $requisition->agreement->access_valid_for_days = $agreementData['access_valid_for_days'];
        $requisition->agreement->save();

        // Create the accounts
        // TODO Change this to: many accounts have many requisitions. This way, the user can modify the account details and re-use them when re-consenting (with another end user agreement and requisition). Use the Nordigen ID to make sure we're dealing with the same account
        foreach ($requisitionData['accounts'] as $accountId) {
            $accountData = $this->client->accountGetDetails($accountId)['account'];

            $requisition->accounts()->create([
                'nordigen_id' => $accountId,
                'currency' => $accountData['currency'],
                'iban' => $accountData['iban'] ?? null,
                'name' => $accountData['name'] ?? null,
            ]);
        }

        DB::commit();

        return true;
    }

    /**
     * @throws SpenderellaNordigenException
     */
    public function fetchTransactions(NordigenAccount $account): array
    {
        $transactionsData = $this->client->accountGetTransactions($account->nordigen_id, null);

        $bookedTransactionsData = $transactionsData['transactions']['booked'];

        DB::beginTransaction();

        $transactions = [];
        foreach ($bookedTransactionsData as $data) {
            $bankId = $data['transactionId'] ?? null;
            $nordigenId = $data['internalTransactionId'] ?? null;

            // Make sure the transaction has any type of ID
            if (! $bankId && ! $nordigenId) {
                throw new SpenderellaNordigenException('Transaction does not contain any ID');
            }

            $transactionExists = $account->transactions();
            if ($bankId) {
                $transactionExists = $transactionExists->where('bank_id', $bankId);
            }
            if ($nordigenId) {
                $transactionExists = $transactionExists->where('nordigen_id', $nordigenId);
            }
            if ($transactionExists->exists()) {
                Log::debug('Transaction already exists, skipping', [
                    'account_id' => $account->id,
                    'bank_id' => $bankId,
                    'nordigen_id' => $nordigenId,
                ]);

                continue;
            }

            $transactions[] = $account->transactions()->create([
                'bank_id' => $bankId,
                'nordigen_id' => $nordigenId,
                'booking_date' => $data['bookingDate'],
                'value_date' => $data['valueDate'],
                'amount' => floatval($data['transactionAmount']['amount']) * 100, // save as cents
                'currency' => $data['transactionAmount']['currency'],
                'description' => $data['remittanceInformationUnstructured'],
            ]);
        }

        DB::commit();

        return $transactions;
    }
}

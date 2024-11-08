<?php

namespace App\Services;

use App\Exceptions\SpenderellaNordigenException;
use App\Exceptions\SpenderellaNordigenUserException;
use App\Integrations\Nordigen\NordigenClient;
use App\Models\NordigenAccount;
use App\Models\NordigenAgreement;
use App\Models\NordigenRequisition;
use App\Models\NordigenTransaction;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NordigenService
{
    const REQUISITION_STATUS_LINKED = 'LN';

    private const CACHE_INSTITUTIONS = 'nordigen.institutions';

    public function __construct(protected NordigenClient $client)
    {
    }

    /**
     * Returns all supported institutions, grouped and sorted by country code
     */
    public function listAllInstitutions(): array
    {
        // Return cached data, if available
        if (Cache::has(self::CACHE_INSTITUTIONS)) {
            Log::debug('Institutions list: HIT');

            return Cache::get(self::CACHE_INSTITUTIONS);
        }

        Log::debug('Institutions list: MISS');

        // Get the data
        $institutions = $this->client->getAllInstitutions();
        $cleanInstitutions = $this->cleanInstitutions($institutions);

        // Cache the data
        Cache::set(self::CACHE_INSTITUTIONS, $cleanInstitutions, now()->addDay());
        Log::debug('Institutions list: SET');

        return $cleanInstitutions;
    }

    /**
     * Groups and sorts institutions by countries and removes redundant data
     */
    private function cleanInstitutions(array $institutions): array
    {
        return collect($institutions)
            ->groupBy('countries')
            ->sortKeys()
            ->map(
                fn (Collection $country) => $country->map(
                    fn (array $institution) => collect($institution)->forget('countries')->all()
                )
            )
            ->all();
    }

    private function getInstitutionById(string $institutionId): array
    {
        return collect($this->listAllInstitutions())
            ->lazy()
            ->flatten(1)
            ->firstOrFail('id', $institutionId);
    }

    /**
     * Creates a Nordigen end user agreement and save it to the database
     */
    public function createEndUserAgreement(string $institutionId, int $daysOfAccess): NordigenAgreement
    {
        // Create the end user agreement on Nordigen's side
        $agreementData = $this->client->endUserAgreementCreate($institutionId, $daysOfAccess);

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

        // Get the maximum days of access for this institution
        $institution = $this->getInstitutionById($institutionId);
        $daysOfAccess = (int) $institution['max_access_valid_for_days'];

        // Create the end user agreement
        $agreement = $this->createEndUserAgreement($institutionId, $daysOfAccess);
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

        Log::debug('New agreement and requisition created', [
            'agreement_id' => $agreement->id,
            'requisition_id' => $requisition->id,
        ]);

        return $requisition;
    }

    /**
     * Tries to update our local Agreement after the user consents access to their data
     *
     *
     * @return bool Returns `false` if the agreement was already updated
     *
     * @throws SpenderellaNordigenException
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
            throw new SpenderellaNordigenException("Account(s) not linked, got status $status");
        }

        // Check if the agreement ID is correct
        $agreementId = $requisition->agreement->nordigen_id;
        if ($requisitionData['agreement'] !== $agreementId) {
            throw new SpenderellaNordigenException("Agreement ID doesn't match");
        }

        // Get the agreement data from Nordigen
        $agreementData = $this->client->getEndUserAgreement($agreementId);

        DB::beginTransaction();

        // Update the agreement
        // Note: update() can't be used here since "accepted_at" needs to be set for the mutator of "access_valid_for_days" to work, since it depends on "accepted_at"
        $requisition->agreement->accepted_at = $agreementData['accepted'];
        $requisition->agreement->access_valid_for_days = $agreementData['access_valid_for_days'];
        $requisition->agreement->save();

        // Create the accounts, if needed
        foreach ($requisitionData['accounts'] as $accountId) {
            $accountData = $this->client->accountGetDetails($accountId)['account'];

            // Check if the account already exists
            $account = NordigenAccount::where('nordigen_id', $accountId)->first();
            if ($account) {
                $account->requisitions()->attach($requisition);

                Log::debug('Account already exists, attaching new requisition', [
                    'requisition_id' => $requisition->id,
                    'account_id' => $accountId,
                ]);

                continue;
            } else {
                $institution = $this->getInstitutionById($requisitionData['institution_id']);

                // Create the account
                $requisition->accounts()->create([
                    'nordigen_id' => $accountId,
                    'currency' => $accountData['currency'],
                    'iban' => $accountData['iban'] ?? null,
                    'name' => $accountData['name'] ?? null,
                    'institution_id' => $institution['id'],
                    'institution_name' => $institution['name'],
                    'institution_bic' => $institution['bic'],
                ]);

                Log::debug('Account created', [
                    'requisition_id' => $requisition->id,
                    'account_id' => $accountId,
                ]);
            }
        }

        DB::commit();

        return true;
    }

    /**
     * @throws SpenderellaNordigenException
     */
    private function loadAndSaveTransactions(NordigenAccount $account): array
    {
        //        if (rand(0, 100) > 70) {
        //            // Succeed 70% of the times
        //            return $account
        //                ->transactions()->take(rand(0, 3))
        //                ->get()
        //                ->all();
        //        } else {
        //            throw new Exception('Get rekt noob');
        //        }

        // Get the transactions from Nordigen
        $transactionsData = $this->client->accountGetTransactions($account->nordigen_id, null);
        $bookedTransactionsData = $transactionsData['transactions']['booked'];

        DB::beginTransaction();

        /** @var NordigenTransaction[] $transactions */
        $transactions = [];
        foreach ($bookedTransactionsData as $data) {
            $bankId = $data['transactionId'] ?? null;
            $nordigenId = $data['internalTransactionId'] ?? null;

            // Make sure the transaction has any type of ID
            if (! $bankId && ! $nordigenId) {
                throw new SpenderellaNordigenException('Transaction does not contain any ID');
            }

            // Make sure the transaction is not in the database yet
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

            // Save the transaction
            $description = $data['remittanceInformationUnstructured']
                ?? implode(' ', $data['remittanceInformationUnstructuredArray'] ?? []);
            $transactions[] = $account->transactions()->create([
                'bank_id' => $bankId,
                'nordigen_id' => $nordigenId,
                'booking_date' => $data['bookingDate'],
                'value_date' => $data['valueDate'],
                'amount' => floatval($data['transactionAmount']['amount']) * 100, // save as cents
                'currency' => $data['transactionAmount']['currency'],
                'description' => $description,
            ]);

            Log::debug('Saving transaction', [
                'account_id' => $account->id,
                'bank_id' => $bankId,
                'nordigen_id' => $nordigenId,
            ]);
        }

        DB::commit();

        return $transactions;
    }

    /**
     * @throws SpenderellaNordigenUserException|SpenderellaNordigenException
     */
    public function syncAccount(NordigenAccount $account): array
    {
        // Check if we still have access to the account
        if (! $account->canSyncTransactions()) {
            throw new SpenderellaNordigenUserException('Please refresh access to the bank account');
        }

        // Since we only sync booked transactions, get those from the week before the last synced one
        // That way, transactions should have enough time to get processed by the bank
        $lastTransaction = $account->transactions()->latest()->first();
        $dateFrom = $lastTransaction?->booking_date->addDays(-7);

        Log::debug($dateFrom ? "Getting transactions from $dateFrom" : 'Getting all transactions', [
            'account_id' => $account->id,
        ]);

        return $this->loadAndSaveTransactions($account, $dateFrom);
    }
}

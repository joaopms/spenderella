<?php

namespace App\DTO;

use App\Models\NordigenAccount;
use App\Models\NordigenTransaction;
use Illuminate\Support\Collection;
use Throwable;

class NordigenSyncResultsDTO
{
    /**
     * @var NordigenSyncResultDTO[]
     */
    private array $results = [];

    public function addResult(NordigenSyncResultDTO $syncResult): void
    {
        $this->results[] = $syncResult;
    }

    /**
     * @param  NordigenTransaction[]  $transactions
     */
    public function addSuccess(NordigenAccount $account, array $transactions): void
    {
        $result = new NordigenSyncResultDTO();
        $result->markAsSuccess($account, $transactions);

        $this->addResult($result);
    }

    public function addFail(NordigenAccount $account, Throwable $exception): void
    {
        $result = new NordigenSyncResultDTO();
        $result->markAsFail($account, $exception);

        $this->addResult($result);
    }

    public function hydrate(): void
    {
        // Load accounts
        $accountsToLoad = collect($this->results)->map(
            fn (NordigenSyncResultDTO $result) => $result->accountId
        );
        $accounts = NordigenAccount::findMany($accountsToLoad);

        foreach ($this->results as $result) {
            $account = $accounts->firstWhere('id', $result->accountId);
            $result->setAccount($account);
        }

        // Load transactions
        $transactionsToLoad = collect($this->results)->flatMap(
            fn (NordigenSyncResultDTO $result) => $result->transactionIds
        );
        $allTransactions = NordigenTransaction::findMany($transactionsToLoad);

        foreach ($this->results as $result) {
            // Skip if we don't have transactions
            $transactionIds = $result->transactionIds;
            if (count($transactionIds) < 1) {
                continue;
            }

            $transactions = $allTransactions->whereIn('id', $transactionIds)->all();
            $result->setTransactions($transactions);
        }
    }

    /**
     * @return Collection<NordigenSyncResultDTO>
     */
    public function getSuccesses(): Collection
    {
        return collect($this->results)
            ->filter(fn (NordigenSyncResultDTO $result) => $result->isSuccess() && $result->hasTransactions());
    }

    /**
     * @return Collection<NordigenSyncResultDTO>
     */
    public function getFails(): Collection
    {
        return collect($this->results)
            ->filter(fn (NordigenSyncResultDTO $result) => ! $result->isSuccess());
    }

    /**
     * @return Collection<NordigenSyncResultDTO>
     */
    public function getAll(bool $hydrated = true): Collection
    {
        if ($hydrated) {
            $this->hydrate();
        }

        return collect($this->results);
    }

    public function countTransactions(): int
    {
        return $this->getSuccesses()
            ->sum(fn (NordigenSyncResultDTO $result) => count($result->transactions));
    }
}

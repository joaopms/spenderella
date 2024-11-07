<?php

namespace App\DTO;

use App\Models\NordigenAccount;
use App\Models\NordigenTransaction;
use Illuminate\Support\Arr;
use Throwable;

class NordigenSyncResultDTO
{
    public int $accountId;

    /** @var int[] */
    public array $transactionIds;

    public ?Throwable $exception;

    private bool $success;

    public array $transactions;

    public NordigenAccount $account;

    public function setAccount(NordigenAccount $account): void
    {
        $this->account = $account;
    }

    /**
     * @param  NordigenTransaction[]  $transactions
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
    }

    /**
     * @param  NordigenTransaction[]  $transactions
     */
    public function markAsSuccess(NordigenAccount $account, array $transactions): void
    {
        $this->accountId = $account->id;
        $this->transactionIds = Arr::pluck($transactions, 'id');
        $this->success = true;
    }

    public function markAsFail(NordigenAccount $account, Throwable $exception): void
    {
        $this->accountId = $account->id;
        $this->exception = $exception;
        $this->success = false;
    }

    public function hasTransactions(): bool
    {
        return count($this->transactions) > 0;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}

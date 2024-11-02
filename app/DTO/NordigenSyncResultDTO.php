<?php

namespace App\DTO;

use App\Models\NordigenAccount;
use App\Models\NordigenTransaction;
use Throwable;

class NordigenSyncResultDTO
{
    public int $accountId;

    public array $transactions;

    public ?Throwable $exception;

    public NordigenAccount $account;

    public function setAccount(NordigenAccount $account): void
    {
        $this->account = $account;
    }

    /**
     * @param  NordigenTransaction[]  $transactions
     */
    public function markAsSuccess(NordigenAccount $account, array $transactions): void
    {
        $this->accountId = $account->id;
        $this->transactions = $transactions;
    }

    public function markAsFail(NordigenAccount $account, Throwable $exception): void
    {
        $this->accountId = $account->id;
        $this->exception = $exception;
    }

    public function isSuccess(): bool
    {
        return ! isset($this->exception) && (bool) $this->transactions;
    }
}

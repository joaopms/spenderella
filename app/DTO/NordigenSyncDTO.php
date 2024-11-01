<?php

namespace App\DTO;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class NordigenSyncDTO
{
    /**
     * New transactions (can grouped by account ID)
     */
    public Collection $transactions;

    /**
     * Exceptions keyed by account ID
     *
     * @var array<integer, Exception>
     */
    public array $errors;

    public function __construct(Collection $transactions, array $errors)
    {
        $this->transactions = $transactions;
        $this->errors = $errors;
    }

    public function getErrorMessagesByAccountId(): array
    {
        return Arr::map($this->errors, fn (Exception $exception) => $exception->getMessage());
    }

    public function getTransactionsByAccountId(): Collection
    {
        return $this->transactions->groupBy('account.id');
    }
}

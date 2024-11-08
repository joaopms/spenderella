<?php

namespace App\Jobs;

use App\Models\NordigenAccount;
use App\Models\NordigenSyncResult;
use App\Services\NordigenService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NordigenSyncAccount implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = NordigenSyncAllAccounts::BACKOFF;

    public function __construct(private NordigenAccount $account)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(NordigenService $nordigen): void
    {
        if ($this->batch()->canceled()) {
            return;
        }

        try {
            $transactions = $nordigen->syncAccount($this->account);

            // Save the result to the database
            NordigenSyncResult::success(
                $this->batch(),
                $this->account,
                $this->job->attempts(),
                $transactions,
            );
        } catch (Throwable $exception) {
            $attempts = $this->job->attempts();
            $maxTries = $this->tries;

            // Save attempt to database
            NordigenSyncResult::fail(
                $this->batch(),
                $this->account,
                $this->job->attempts(),
                $exception,
            );

            if ($attempts < $maxTries) {
                // Log that we still have attempts
                Log::info('Failed to sync account, will try again', [
                    'attempts' => $attempts,
                    'maxTries' => $maxTries,
                ]);
            } else {
                // No more attempts
                Log::error('Failed to sync account, attempts exhausted', [
                    'attempts' => $attempts,
                    'maxTries' => $maxTries,
                ]);
            }

            throw $exception;
        }
    }
}

<?php

namespace App\Jobs;

use App\Events\NordigenAccountsSynced;
use App\Events\NordigenAccountsSyncFailed;
use App\Models\NordigenAccount;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class NordigenSyncAllAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const BACKOFF = [
        15 * 60, // 15 minutes
        60 * 60, // 60 minutes
        4 * 60 * 60, // 4 hours
    ];

    public int $tries = 3;

    public array $backoff = self::BACKOFF;

    public function handle(): void
    {
        Log::info('Preparing to sync all Nordigen accounts');

        // Get the accounts to sync and create the jobs
        $accounts = NordigenAccount::all()
            ->filter(fn (NordigenAccount $account) => $account->canSyncTransactions());

        $jobs = $accounts->map(fn (NordigenAccount $account) => new NordigenSyncAccount($account));

        // Create the job batch and dispatch it
        $batch = Bus::batch($jobs)
            ->name('Nordigen: Sync All Accounts')
            ->allowFailures()
            ->finally(function (Batch $batch) {
                Log::info('Accounts were synced', [
                    'batch_id' => $batch->id,
                    'failed_jobs' => $batch->failedJobs,
                    'total_jobs' => $batch->totalJobs,
                ]);

                NordigenAccountsSynced::dispatch($batch->id);
            })
            ->dispatch();

        Log::info("Will be syncing {$batch->totalJobs} account(s)", [
            'account_ids' => $accounts->pluck('id')->all(),
            'batch_id' => $batch->id,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Error scheduling the sync of all Nordigen accounts', [
            'exception' => $exception,
        ]);
        NordigenAccountsSyncFailed::dispatch($exception);

        // Consider as failed if there are no attempts left
        //        if ($this->attempts() >= $this->job->maxTries()) {
        //        }
    }
}

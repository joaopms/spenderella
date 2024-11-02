<?php

namespace App\Jobs;

use App\DTO\NordigenSyncResultsDTO;
use App\Models\NordigenAccount;
use App\Providers\NordigenAccountsSynced;
use App\Providers\NordigenAccountsSyncFailed;
use App\Services\NordigenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class NordigenSyncAllAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [
            15 * 60, // 15 minutes
            60 * 60, // 60 minutes
            4 * 60 * 60, // 4 hours
        ];
    }

    public function handle(NordigenService $nordigen): void
    {
        //        $results = new NordigenSyncResultsDTO();
        //
        //        $accounts = NordigenAccount::all();
        //        foreach ($accounts as $account) {
        //            if (mt_rand(0, 1)) {
        //                $results->addSuccess(
        //                    $account,
        //                    $account->transactions()->take(2)->get()->all()
        //                );
        //            } else {
        //                $results->addFail(
        //                    $account,
        //                    new \Exception('oops')
        //                );
        //            }
        //        }
        //
        //        NordigenAccountsSynced::dispatch($results);

        $results = $nordigen->syncAllAccounts();

        NordigenAccountsSynced::dispatch($results);
    }

    public function failed(?Throwable $exception): void
    {
        // Consider as failed if there are no attempts left
        //        if ($this->attempts() >= $this->job->maxTries()) {
        NordigenAccountsSyncFailed::dispatch($exception);
        //        }
    }
}

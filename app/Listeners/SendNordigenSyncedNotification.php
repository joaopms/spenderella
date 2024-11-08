<?php

namespace App\Listeners;

use App\Events\NordigenAccountsSynced;
use App\Mail\NordigenSyncSuccess;
use App\Models\NordigenSyncResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNordigenSyncedNotification implements ShouldQueue
{
    public function handle(NordigenAccountsSynced $event): void
    {
        $batchId = $event->batchId;
        $results = collect(NordigenSyncResult::getByBatchId($batchId))
            ->groupBy('success');

        $successes = $results->get(true, collect())
            ->filter(fn (NordigenSyncResult $result) => $result->hasTransactions());
        $fails = $results->get(false, collect());

        $numTransactions = $successes
            ->sum(fn (NordigenSyncResult $result) => count($result->transaction_ids));

        Log::debug('Accounts synced', [
            'success' => count($successes),
            'errors' => count($fails),
            'num_transactions' => $numTransactions,
        ]);

        Mail::to(config('mail.receiver'))
            ->send(new NordigenSyncSuccess(
                $successes->all(),
                $fails->all(),
                $numTransactions
            ));
    }
}

<?php

namespace App\Listeners;

use App\Events\NordigenAccountsSynced;
use App\Mail\NordigenSyncSuccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNordigenSyncedNotification implements ShouldQueue
{
    public function handle(NordigenAccountsSynced $event): void
    {
        $results = $event->results;

        Log::debug('Accounts synced', [
            'errors' => count($results->getFails()),
            'success' => count($results->getSuccesses()),
        ]);

        Mail::to(config('mail.receiver'))
            ->send(new NordigenSyncSuccess($event->results));
    }
}

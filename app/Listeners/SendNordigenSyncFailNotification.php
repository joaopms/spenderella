<?php

namespace App\Listeners;

use App\Events\NordigenAccountsSyncFailed;
use App\Mail\NordigenSyncFail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNordigenSyncFailNotification
{
    public function handle(NordigenAccountsSyncFailed $event): void
    {
        Log::debug('Account sync failed');

        Mail::to(config('mail.receiver'))
            ->send(new NordigenSyncFail($event->exception));
    }
}

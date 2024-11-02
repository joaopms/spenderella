<?php

namespace App\Providers;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class NordigenAccountsSyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public ?Throwable $exception)
    {
    }
}

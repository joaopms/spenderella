<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NordigenAccountsSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $batchId)
    {
    }
}

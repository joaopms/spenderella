<?php

namespace App\Events;

use App\DTO\NordigenSyncResultsDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NordigenAccountsSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(public NordigenSyncResultsDTO $results)
    {
    }
}

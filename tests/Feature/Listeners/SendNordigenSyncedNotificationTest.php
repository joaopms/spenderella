<?php

namespace Tests\Feature\Listeners;

use App\Events\NordigenAccountsSynced;
use App\Mail\NordigenSyncSuccess;
use App\Models\NordigenSyncResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNordigenSyncedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_success_email()
    {
        Mail::fake();

        $sync = NordigenSyncResult::factory()->make();

        NordigenAccountsSynced::dispatch($sync->batch_id);

        Mail::assertSentCount(1);
        Mail::assertSent(NordigenSyncSuccess::class);
    }
}

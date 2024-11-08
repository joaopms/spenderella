<?php

namespace App\Mail;

use App\Models\NordigenSyncResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NordigenSyncSuccess extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  NordigenSyncResult[]  $successes
     * @param  NordigenSyncResult[]  $fails
     */
    public function __construct(
        private readonly array $successes,
        private readonly array $fails,
        private readonly int $numTransactions
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sync: New transactions',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.nordigen.sync-success',
            with: [
                'fails' => $this->fails,
                'successes' => $this->successes,
                'numTransactions' => $this->numTransactions,
            ]
        );
    }
}

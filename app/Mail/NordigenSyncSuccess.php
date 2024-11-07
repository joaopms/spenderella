<?php

namespace App\Mail;

use App\DTO\NordigenSyncResultsDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NordigenSyncSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NordigenSyncResultsDTO $results)
    {
        $this->results->hydrate();
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
                'fails' => $this->results->getFails(),
                'successes' => $this->results->getSuccesses(),
            ]
        );
    }
}

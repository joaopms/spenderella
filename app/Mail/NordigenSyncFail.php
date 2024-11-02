<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class NordigenSyncFail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ?Throwable $exception)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sync: Failed to sync transactions',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.nordigen.sync-fail',
        );
    }
}

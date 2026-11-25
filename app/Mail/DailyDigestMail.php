<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The daily summary of what needs attention (App\Services\Mail\DailyDigest).
 */
class DailyDigestMail extends BaseMailable
{
    /**
     * @param  array<string, array{count: int, items: list<array{label: string, detail: string, url: string}>}>  $sections
     */
    public function __construct(public readonly array $sections) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            subject: trans('mail-settings.digest.subject', ['count' => array_sum(array_column($this->sections, 'count'))]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'notifications.markdown.daily-digest', with: ['sections' => $this->sections]);
    }
}

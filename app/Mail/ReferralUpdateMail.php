<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Report $report,
        private readonly string $subjectLine,
        private readonly string $bodyLine
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.referral-update',
            with: [
                'subjectLine' => $this->subjectLine,
                'bodyLine' => $this->bodyLine,
                'trackingCode' => $this->report->tracking_code,
                'trackingUrl' => route('track.show', $this->report->tracking_code),
            ],
        );
    }
}

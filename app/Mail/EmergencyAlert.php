<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmergencyAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private readonly Report $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Emergency report requires staff attention',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.emergency-alert',
            with: [
                'trackingCode' => $this->report->tracking_code,
                'purokName' => $this->report->purok?->name ?? 'Not specified',
                'reportedAt' => $this->report->created_at,
                'reviewUrl' => route('admin.reports.show', $this->report),
            ],
        );
    }
}

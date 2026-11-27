<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportReceivedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Report $report
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Barangay Calarian — Report Received ['.$this->report->tracking_code.']',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.report-received',
            with: [
                'report' => $this->report,
                'trackingCode' => $this->report->tracking_code,
                'trackingUrl' => route('track.show', $this->report->tracking_code),
                'categoryName' => $this->report->category?->name ?? 'General Concern',
                'purokName' => $this->report->purok?->name ?? 'Not specified',
                'reportedAt' => $this->report->created_at,
                'reporterName' => $this->report->reporter_name,
            ],
        );
    }
}

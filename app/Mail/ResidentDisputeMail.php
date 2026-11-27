<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\Report;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResidentDisputeMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Report $report,
        public readonly ?string $disputeReason = null,
        public readonly ?Feedback $feedback = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resident Disputed Resolution — Report ['.$this->report->tracking_code.']',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.resident-dispute',
            with: [
                'report' => $this->report,
                'trackingCode' => $this->report->tracking_code,
                'categoryName' => $this->report->category?->name ?? 'General Concern',
                'purokName' => $this->report->purok?->name ?? 'Not specified',
                'disputeReason' => $this->disputeReason,
                'feedbackComment' => $this->feedback?->comment,
                'feedbackRating' => $this->feedback?->rating,
                'reviewUrl' => route('admin.reports.show', $this->report),
            ],
        );
    }
}

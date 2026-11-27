<?php

namespace App\Mail;

use App\Models\Report;
use App\Models\ResolutionProof;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResolutionProposedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Report $report,
        public readonly ResolutionProof $proof
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Barangay Calarian — Resolution Proposed ['.$this->report->tracking_code.']',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.resolution-proposed',
            with: [
                'report' => $this->report,
                'proof' => $this->proof,
                'trackingCode' => $this->report->tracking_code,
                'trackingUrl' => route('track.show', $this->report->tracking_code),
                'resolutionNote' => $this->proof->note,
                'reporterName' => $this->report->reporter_name,
            ],
        );
    }
}

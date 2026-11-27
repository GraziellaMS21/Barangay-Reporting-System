<?php

namespace App\Mail;

use App\Models\InfoRequest;
use App\Models\Report;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InfoRequestedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Report $report,
        public readonly InfoRequest $infoRequest
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Barangay Calarian — Additional Information Needed ['.$this->report->tracking_code.']',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.info-requested',
            with: [
                'report' => $this->report,
                'infoRequest' => $this->infoRequest,
                'trackingCode' => $this->report->tracking_code,
                'trackingUrl' => route('track.show', $this->report->tracking_code),
                'question' => $this->infoRequest->question,
                'reporterName' => $this->report->reporter_name,
            ],
        );
    }
}

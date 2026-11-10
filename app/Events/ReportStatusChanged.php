<?php
namespace App\Events;
use App\Models\Report;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class ReportStatusChanged { use Dispatchable, SerializesModels; public function __construct(public Report $report, public ?string $from, public string $to, public ?string $note=null, public ?int $userId=null) {} }

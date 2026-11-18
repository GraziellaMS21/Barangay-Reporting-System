<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportProofPhoto extends Model
{
    protected $table = 'report_proof_photos';

    protected $fillable = ['report_id', 'path', 'uploaded_by'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

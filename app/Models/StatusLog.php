<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusLog extends Model
{
    protected $fillable = ['report_id', 'user_id', 'from_status', 'to_status', 'note'];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}

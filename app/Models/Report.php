<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    protected $fillable = [
        'tracking_code',
        'description',
        'photo_path',
        'lat',
        'lng',
        'purok_id',
        'landmark',
        'reporter_email',
        'status',
    ];

    public function purok()
    {
        return $this->belongsTo(Purok::class);
    }
    public function statusLogs()
    {
        return $this->hasMany(StatusLog::class)->oldest();
    }

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'BRG-' . strtoupper(Str::random(6));
        } while (static::where('tracking_code', $code)->exists());

        return $code;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensitivePlace extends Model
{
    protected $fillable = ['name', 'type', 'lat', 'lng'];
}

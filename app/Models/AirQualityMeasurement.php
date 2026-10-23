<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirQualityMeasurement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'city',
        'recorded_at',
        'pm2_5',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'immutable_datetime',
            'pm2_5' => 'decimal:3',
        ];
    }
}

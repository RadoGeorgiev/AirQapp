<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirQualitySync extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'status',
        'requested_from',
        'requested_to',
        'rows_synced',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_from' => 'date',
            'requested_to' => 'date',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}

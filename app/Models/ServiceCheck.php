<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCheck extends Model
{
    protected $fillable = [
        'monitored_service_id',
        'status',
        'response_time_ms',
        'message',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(MonitoredService::class, 'monitored_service_id');
    }
}

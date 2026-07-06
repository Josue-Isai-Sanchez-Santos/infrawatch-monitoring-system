<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'monitored_host_id',
        'monitored_service_id',
        'type',
        'severity',
        'title',
        'message',
        'status',
        'triggered_at',
        'resolved_at',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(MonitoredHost::class, 'monitored_host_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(MonitoredService::class, 'monitored_service_id');
    }
}

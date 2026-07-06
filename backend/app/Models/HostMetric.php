<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostMetric extends Model
{
    protected $fillable = [
        'monitored_host_id',
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'uptime_seconds',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(MonitoredHost::class, 'monitored_host_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredHost extends Model
{
    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'operating_system',
        'host_type',
        'location',
        'status',
        'agent_token',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(MonitoredService::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(HostMetric::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}

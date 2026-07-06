<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredService extends Model
{
    protected $fillable = [
        'monitored_host_id',
        'name',
        'port',
        'protocol',
        'status',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(MonitoredHost::class, 'monitored_host_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ServiceCheck::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}

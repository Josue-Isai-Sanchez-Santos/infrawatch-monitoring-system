<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

class HostMetric extends Model
{
    protected $fillable = [
        'monitored_host_id',
        'reading_id',
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'uptime_seconds',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    /**
     * Una lectura por host: la fecha más reciente y, en empate, el mayor ID.
     *
     * No se usa MAX(id) como sustituto de recorded_at: una importación
     * histórica puede recibir IDs superiores a los de lecturas recientes.
     */
    #[Scope]
    protected function latestPerHost(Builder $query): void
    {
        $query->where('host_metrics.id', '=', function (QueryBuilder $subquery): void {
            $subquery->select('candidate.id')
                ->from('host_metrics as candidate')
                ->whereColumn('candidate.monitored_host_id', 'host_metrics.monitored_host_id')
                ->orderByDesc('candidate.recorded_at')
                ->orderByDesc('candidate.id')
                ->limit(1);
        });
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(
            MonitoredHost::class,
            'monitored_host_id'
        );
    }
}

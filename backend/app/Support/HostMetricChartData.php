<?php

namespace App\Support;

use App\Models\HostMetric;
use InvalidArgumentException;

final class HostMetricChartData
{
    private const ALLOWED_FIELDS = [
        'cpu_usage',
        'ram_usage',
        'disk_usage',
    ];

    public static function forHost(mixed $hostId, string $field, string $label): array
    {
        if (! in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new InvalidArgumentException('Unsupported metric field.');
        }

        $empty = self::chart([], [], $label);

        // Filament page filters are user-controlled; never trust the input.
        if (! is_string($hostId) && ! is_int($hostId)) {
            return $empty;
        }

        if (! preg_match('/^[1-9][0-9]*$/D', (string) $hostId)) {
            return $empty;
        }

        $metrics = HostMetric::query()
            ->where('monitored_host_id', (int) $hostId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'recorded_at', $field])
            ->reverse()
            ->values();

        return self::chart(
            $metrics->map(fn (HostMetric $metric): string => $metric->recorded_at->format('d/m H:i:s'))->all(),
            $metrics->map(fn (HostMetric $metric): ?float => $metric->{$field} === null
                ? null
                : (float) $metric->{$field})->all(),
            $label,
        );
    }

    private static function chart(array $labels, array $values, string $label): array
    {
        return [
            'datasets' => [[
                'label' => $label,
                'data' => $values,
                'tension' => 0.25,
                'spanGaps' => false,
                'pointRadius' => 2,
            ]],
            'labels' => $labels,
        ];
    }
}

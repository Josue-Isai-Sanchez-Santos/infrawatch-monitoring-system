<?php

namespace App\Filament\Widgets;

use App\Models\HostMetric;
use Filament\Widgets\ChartWidget;

class DiskUsageChart extends ChartWidget
{
    protected static ?string $heading = 'Uso de disco';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $metrics = HostMetric::query()
            ->with('host')
            ->whereNotNull('disk_usage')
            ->latest('recorded_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Disco %',
                    'data' => $metrics->map(fn (HostMetric $metric) => (float) $metric->disk_usage)->toArray(),
                    'tension' => 0.3,
                ],
            ],
            'labels' => $metrics->map(function (HostMetric $metric) {
                $hostName = $metric->host?->name ?? 'Host';
                $time = $metric->recorded_at?->format('H:i:s') ?? '';

                return "{$hostName} {$time}";
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

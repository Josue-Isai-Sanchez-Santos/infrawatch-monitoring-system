<?php

namespace App\Filament\Widgets;

use App\Support\HostMetricChartData;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CpuUsageChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Uso de CPU';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?array $options = [
        'scales' => [
            'y' => ['min' => 0, 'max' => 100],
        ],
    ];

    protected function getData(): array
    {
        return HostMetricChartData::forHost(
            $this->filters['host_id'] ?? null,
            'cpu_usage',
            'CPU %',
        );
    }

    protected function getType(): string
    {
        return 'line';
    }
}

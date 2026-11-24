<?php

namespace App\Filament\Widgets;

use App\Support\HostMetricChartData;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RamUsageChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Uso de RAM';

    protected static ?int $sort = 5;

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
            'ram_usage',
            'RAM %',
        );
    }

    protected function getType(): string
    {
        return 'line';
    }
}

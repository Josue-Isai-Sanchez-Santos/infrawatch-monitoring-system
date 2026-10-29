<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable;
    use SerializesModels;

    // Nunca usar la cola sync para este evento.
    public string $connection = 'database';

    // Política de reintentos del broadcast.
    public int $tries = 3;

    public int $timeout = 15;

    public int $backoff = 10;

    public string $occurredAt;

    public function __construct(
        public string $type = 'dashboard_updated',
        public ?string $message = null,
        public ?int $metricId = null,
    ) {
        $this->occurredAt = now()->toIso8601String();
    }

    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }

    public function broadcastOn(): Channel
    {
        return new Channel('infrawatch.dashboard');
    }

    public function broadcastAs(): string
    {
        return 'dashboard.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'metric_id' => $this->metricId,
            'timestamp' => $this->occurredAt,
        ];
    }
}

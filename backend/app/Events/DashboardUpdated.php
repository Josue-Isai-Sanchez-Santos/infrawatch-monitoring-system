<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $type = 'dashboard_updated',
        public ?string $message = null,
    ) {}

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
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}

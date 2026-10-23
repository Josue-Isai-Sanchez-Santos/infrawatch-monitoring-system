<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Services\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class SendTelegramAlert implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    public function __construct(
        public int $alertId,
        public string $action,
    ) {}

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(TelegramNotifier $notifier): void
    {
        if (! config('services.telegram.enabled')) {
            return;
        }

        $alert = Alert::find($this->alertId);

        if (! $alert) {
            return;
        }

        match ($this->action) {
            'created' => $notifier->sendAlertCreated($alert),
            'resolved' => $notifier->sendAlertResolved($alert),
            default => throw new InvalidArgumentException(
                'Unsupported Telegram alert action.'
            ),
        };
    }
}

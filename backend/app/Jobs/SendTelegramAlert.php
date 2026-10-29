<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Services\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class SendTelegramAlert implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // Número máximo de intentos.
    public int $tries = 3;

    // Tiempo máximo de cada intento.
    public int $timeout = 20;

    public function __construct(
        public int $alertId,
        public string $action,
    ) {
        // Impide que una configuración global "sync"
        // convierta el envío en una operación síncrona.
        $this->onConnection('database');
    }

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

    /**
     * Se ejecuta cuando el trabajo agota
     * los intentos disponibles.
     */
    public function failed(Throwable $exception): void
    {
        Log::error(
            'Telegram alert delivery permanently failed.',
            [
                'alert_id' => $this->alertId,
                'action' => $this->action,
                'exception_type' => get_class($exception),
            ]
        );
    }
}

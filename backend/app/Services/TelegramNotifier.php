<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramNotifier
{
    public function sendAlertCreated(Alert $alert): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $alert->loadMissing(['host', 'service']);

        $host = $alert->host;
        $service = $alert->service;

        $message = implode("\n", [
            '🚨 InfraWatch Alert',
            '',
            'Severidad: '.strtoupper($alert->severity),
            'Equipo: '.($host?->name ?? 'N/A'),
            'Servicio: '.($service?->name ?? 'N/A'),
            'IP: '.($host?->ip_address ?? 'N/A'),
            'Puerto: '.($service?->port ?? 'N/A'),
            '',
            $alert->title,
            '',
            $alert->message ?? 'Sin mensaje adicional.',
        ]);

        $this->sendMessage($message);
    }

    public function sendAlertResolved(Alert $alert): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $alert->loadMissing(['host', 'service']);

        $host = $alert->host;
        $service = $alert->service;

        $message = implode("\n", [
            '✅ InfraWatch Recovery',
            '',
            'Equipo: '.($host?->name ?? 'N/A'),
            'Servicio: '.($service?->name ?? 'N/A'),
            'IP: '.($host?->ip_address ?? 'N/A'),
            'Puerto: '.($service?->port ?? 'N/A'),
            '',
            'El servicio volvió a estar disponible.',
        ]);

        $this->sendMessage($message);
    }

    private function sendMessage(string $message): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::warning(
                'Telegram notification skipped: missing credentials.'
            );

            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->post($url, [
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);

        } catch (ConnectionException $exception) {

            // No registramos el mensaje original de la
            // excepción: podría incluir la URL con el
            // token secreto del bot de Telegram.

            Log::warning(
                'No se pudo conectar con Telegram.',
                [
                    'exception_type' => get_class($exception),
                ]
            );

            // Lanzamos una excepción sin credenciales
            // para que el worker reintente este trabajo.
            throw new RuntimeException(
                'Telegram connection failed.'
            );
        }

        if (! $response->successful()) {

            Log::warning(
                'Telegram notification failed.',
                [
                    'status' => $response->status(),
                ]
            );

            // Los fallos HTTP también serán procesados
            // por la política de reintentos del job.
            throw new RuntimeException(
                'Telegram API returned HTTP '.$response->status()
            );
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.telegram.enabled');
    }
}

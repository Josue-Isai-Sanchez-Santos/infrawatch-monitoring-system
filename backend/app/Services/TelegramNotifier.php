<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            Log::warning('Telegram notification skipped: missing bot token or chat id.');

            return;
        }

        $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
        ]);

        if (! $response->successful()) {
            Log::warning('Telegram notification failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.telegram.enabled');
    }
}

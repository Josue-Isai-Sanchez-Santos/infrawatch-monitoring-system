<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramAlert;
use App\Models\Alert;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use App\Services\TelegramNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class TelegramQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_dispatches_a_job_when_service_goes_offline(): void
    {
        Queue::fake();

        config([
            'services.telegram.enabled' => true,
        ]);

        $host = MonitoredHost::create([
            'name' => 'Servidor de prueba',
            'hostname' => 'test-host',
            'ip_address' => '127.0.0.1',
            'host_type' => 'server',
            'status' => 'online',
        ]);

        MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'Servicio de prueba',
            'port' => 1,
            'protocol' => 'tcp',
            'status' => 'unknown',
        ]);

        $this->artisan('monitor:services')->assertSuccessful();

        $alert = Alert::query()->firstOrFail();

        Queue::assertPushed(
            SendTelegramAlert::class,
            fn (SendTelegramAlert $job): bool => $job->alertId === $alert->id
                && $job->action === 'created'
        );
    }

    public function test_job_sends_telegram_notification_using_mock_http(): void
    {
        config([
            'services.telegram.enabled' => true,
            'services.telegram.bot_token' => 'fake-token',
            'services.telegram.chat_id' => 'fake-chat',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
            ], 200),
        ]);

        $alert = $this->createTestAlert();

        $job = new SendTelegramAlert(
            $alert->id,
            'created'
        );

        $job->handle(app(TelegramNotifier::class));

        Http::assertSent(
            fn ($request): bool => $request->url() ===
                    'https://api.telegram.org/botfake-token/sendMessage'
                && $request['chat_id'] === 'fake-chat'
                && str_contains(
                    $request['text'],
                    'InfraWatch Alert'
                )
        );

        Http::assertSentCount(1);
    }

    public function test_job_throws_exception_when_telegram_returns_error(): void
    {
        config([
            'services.telegram.enabled' => true,
            'services.telegram.bot_token' => 'fake-token',
            'services.telegram.chat_id' => 'fake-chat',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => false,
            ], 500),
        ]);

        $alert = $this->createTestAlert();

        $this->expectException(RuntimeException::class);

        $job = new SendTelegramAlert(
            $alert->id,
            'created'
        );

        $job->handle(app(TelegramNotifier::class));
    }

    private function createTestAlert(): Alert
    {
        $host = MonitoredHost::create([
            'name' => 'Servidor Telegram Test',
            'hostname' => 'telegram-test',
            'ip_address' => '192.0.2.10',
            'host_type' => 'server',
            'status' => 'offline',
        ]);

        $service = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'PostgreSQL Test',
            'port' => 5432,
            'protocol' => 'tcp',
            'status' => 'offline',
        ]);

        return Alert::create([
            'monitored_host_id' => $host->id,
            'monitored_service_id' => $service->id,
            'type' => 'service_down',
            'severity' => 'critical',
            'title' => 'Servicio caído: PostgreSQL Test',
            'message' => 'Servicio de prueba no disponible.',
            'status' => 'open',
            'triggered_at' => now(),
        ]);
    }
}

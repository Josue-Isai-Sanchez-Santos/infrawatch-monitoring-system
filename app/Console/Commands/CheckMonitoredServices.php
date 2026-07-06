<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\MonitoredService;
use App\Models\ServiceCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckMonitoredServices extends Command
{
    protected $signature = 'monitor:services';

    protected $description = 'Check monitored services availability by testing TCP ports';

    public function handle(): int
    {
        $services = MonitoredService::with('host')->get();

        if ($services->isEmpty()) {
            $this->warn('No monitored services found.');
            return self::SUCCESS;
        }

        $this->info('Checking monitored services...');

        foreach ($services as $service) {
            $host = $service->host;

            if (! $host) {
                $this->warn("Service {$service->name} has no host assigned.");
                continue;
            }

            $ip = $host->ip_address;
            $port = (int) $service->port;

            $startTime = microtime(true);

            $connection = @fsockopen($ip, $port, $errorCode, $errorMessage, 3);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($connection) {
                fclose($connection);

                $status = 'online';
                $message = "Service {$service->name} is reachable on {$ip}:{$port}.";

                $this->info("[ONLINE] {$host->name} - {$service->name} ({$ip}:{$port})");
            } else {
                $status = 'offline';
                $message = "Service {$service->name} is not reachable on {$ip}:{$port}. Error: {$errorMessage}";

                $this->error("[OFFLINE] {$host->name} - {$service->name} ({$ip}:{$port})");
            }

            ServiceCheck::create([
                'monitored_service_id' => $service->id,
                'status' => $status,
                'response_time_ms' => $responseTimeMs,
                'message' => $message,
                'checked_at' => Carbon::now(),
            ]);

            $service->update([
                'status' => $status,
                'last_checked_at' => Carbon::now(),
            ]);

            $host->update([
                'status' => $status,
                'last_seen_at' => $status === 'online' ? Carbon::now() : $host->last_seen_at,
            ]);

            if ($status === 'offline') {
                Alert::firstOrCreate(
                    [
                        'monitored_service_id' => $service->id,
                        'status' => 'open',
                        'type' => 'service_down',
                    ],
                    [
                        'monitored_host_id' => $host->id,
                        'severity' => 'critical',
                        'title' => "Servicio caído: {$service->name}",
                        'message' => $message,
                        'triggered_at' => Carbon::now(),
                    ]
                );
            }

            if ($status === 'online') {
                Alert::where('monitored_service_id', $service->id)
                    ->where('type', 'service_down')
                    ->where('status', 'open')
                    ->update([
                        'status' => 'resolved',
                        'resolved_at' => Carbon::now(),
                    ]);
            }
        }

        $this->info('Service check completed.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Events\DashboardUpdated;
use App\Http\Controllers\Controller;
use App\Models\HostMetric;
use App\Models\MonitoredHost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgentMetricController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Missing agent token.',
            ], 401);
        }

        $validated = $request->validate([
            'reading_id' => [
                'nullable',
                'uuid',
            ],
            'hostname' => [
                'required',
                'string',
                'max:255',
            ],
            'ip_address' => [
                'required',
                'ip',
            ],
            'operating_system' => [
                'nullable',
                'string',
                'max:255',
            ],
            'cpu_usage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'ram_usage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'disk_usage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'uptime_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $host = MonitoredHost::query()
            ->where('agent_token', $token)
            ->first();

        if (! $host) {
            return response()->json([
                'message' => 'Invalid agent token.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Guardado atómico e idempotente
        |--------------------------------------------------------------------------
        |
        | PostgreSQL guarda la métrica y actualiza
        | el estado del agente en una transacción.
        |
        | Si reading_id ya existe para este host,
        | recuperamos la métrica anterior.
        |
        */

        $readingId = $validated['reading_id'] ?? null;

        $metric = DB::transaction(
            function () use (
                $host,
                $validated,
                $readingId
            ): HostMetric {

                $metricData = [
                    'cpu_usage' => $validated['cpu_usage'],
                    'ram_usage' => $validated['ram_usage'],
                    'disk_usage' => $validated['disk_usage'],
                    'uptime_seconds' => $validated['uptime_seconds'] ?? null,
                    'recorded_at' => Carbon::now(),
                ];

                if ($readingId !== null) {

                    // firstOrCreate aprovecha el índice único
                    // para recuperar lecturas repetidas.
                    $metric = HostMetric::query()
                        ->firstOrCreate(
                            [
                                'monitored_host_id' => $host->id,
                                'reading_id' => $readingId,
                            ],
                            $metricData
                        );

                } else {

                    // Compatibilidad con agentes antiguos.
                    $metric = HostMetric::create(
                        array_merge(
                            [
                                'monitored_host_id' => $host->id,
                                'reading_id' => null,
                            ],
                            $metricData
                        )
                    );
                }

                // Un reintento no debe modificar
                // nuevamente la fecha del agente.
                if ($metric->wasRecentlyCreated) {
                    $host->update([
                        'hostname' => $validated['hostname'],
                        'ip_address' => $validated['ip_address'],
                        'operating_system' => $validated['operating_system']
                            ?? $host->operating_system,
                        'status' => 'online',
                        'last_seen_at' => Carbon::now(),
                    ]);
                }

                return $metric;
            }
        );

        $created = $metric->wasRecentlyCreated;

        /*
        |--------------------------------------------------------------------------
        | Evento asíncrono
        |--------------------------------------------------------------------------
        |
        | Solo emitimos cuando se creó una métrica.
        | Se ejecuta después de confirmar la transacción.
        |
        | Un fallo de broadcasting no debe convertir
        | una métrica guardada en un HTTP 500.
        |
        */

        if ($created) {
            try {
                DashboardUpdated::dispatch(
                    type: 'host_metric_created',
                    message: 'Nueva métrica recibida desde el agente.',
                    metricId: $metric->id
                );

            } catch (Throwable $exception) {

                Log::warning(
                    'No se pudo despachar la actualización del dashboard.',
                    [
                        'metric_id' => $metric->id,
                        'exception_type' => get_class($exception),
                    ]
                );
            }
        }

        return response()->json([
            'message' => $created
                ? 'Metrics stored successfully.'
                : 'Metrics already stored.',
            'metric_id' => $metric->id,
            'reading_id' => $metric->reading_id,
        ], $created ? 201 : 200);
    }
}

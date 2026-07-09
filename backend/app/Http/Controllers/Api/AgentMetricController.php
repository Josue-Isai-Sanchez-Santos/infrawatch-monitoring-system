<?php

namespace App\Http\Controllers\Api;

use App\Events\DashboardUpdated;
use App\Http\Controllers\Controller;
use App\Models\HostMetric;
use App\Models\MonitoredHost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
            'hostname' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:45'],
            'operating_system' => ['nullable', 'string', 'max:255'],
            'cpu_usage' => ['required', 'numeric', 'min:0', 'max:100'],
            'ram_usage' => ['required', 'numeric', 'min:0', 'max:100'],
            'disk_usage' => ['required', 'numeric', 'min:0', 'max:100'],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $host = MonitoredHost::where('agent_token', $token)->first();

        if (! $host) {
            return response()->json([
                'message' => 'Invalid agent token.',
            ], 403);
        }

        $host->update([
            'hostname' => $validated['hostname'],
            'ip_address' => $validated['ip_address'],
            'operating_system' => $validated['operating_system'] ?? $host->operating_system,
            'status' => 'online',
            'last_seen_at' => Carbon::now(),
        ]);

        $metric = HostMetric::create([
            'monitored_host_id' => $host->id,
            'cpu_usage' => $validated['cpu_usage'],
            'ram_usage' => $validated['ram_usage'],
            'disk_usage' => $validated['disk_usage'],
            'uptime_seconds' => $validated['uptime_seconds'] ?? null,
            'recorded_at' => Carbon::now(),
        ]);

        DashboardUpdated::dispatch(
            type: 'host_metric_created',
            message: 'Nueva métrica recibida desde el agente.'
        );

        return response()->json([
            'message' => 'Metrics stored successfully.',
            'metric_id' => $metric->id,
        ], 201);
    }
}

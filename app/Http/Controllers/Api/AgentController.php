<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    /**
     * Register agent with server.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'agent_token' => 'required|string',
            'hostname' => 'required|string',
            'ip_address' => 'required|ip',
            'os' => 'nullable|string',
        ]);

        $server = Server::where('agent_token', $validated['agent_token'])->first();

        if (!$server) {
            return response()->json(['error' => 'Invalid agent token'], 401);
        }

        $server->update([
            'hostname' => $validated['hostname'],
            'ip_address' => $validated['ip_address'],
            'os' => $validated['os'] ?? $server->os,
            'last_seen' => now(),
        ]);

        return response()->json([
            'server_id' => $server->id,
            'status' => 'registered',
        ]);
    }

    /**
     * Report metrics from agent.
     */
    public function reportMetrics(Request $request)
    {
        $validated = $request->validate([
            'agent_token' => 'required|string',
            'cpu_usage' => 'nullable|numeric|min:0|max:100',
            'memory_used_mb' => 'nullable|integer|min:0',
            'memory_total_mb' => 'nullable|integer|min:0',
            'disk_used_gb' => 'nullable|integer|min:0',
            'disk_total_gb' => 'nullable|integer|min:0',
            'load_average_1m' => 'nullable|numeric|min:0',
            'load_average_5m' => 'nullable|numeric|min:0',
            'load_average_15m' => 'nullable|numeric|min:0',
        ]);

        $server = Server::where('agent_token', $validated['agent_token'])->first();

        if (!$server) {
            return response()->json(['error' => 'Invalid agent token'], 401);
        }

        $server->update(['last_seen' => now()]);

        ServerMetric::create([
            'server_id' => $server->id,
            'cpu_usage' => $validated['cpu_usage'] ?? null,
            'memory_used_mb' => $validated['memory_used_mb'] ?? null,
            'memory_total_mb' => $validated['memory_total_mb'] ?? null,
            'disk_used_gb' => $validated['disk_used_gb'] ?? null,
            'disk_total_gb' => $validated['disk_total_gb'] ?? null,
            'load_average_1m' => $validated['load_average_1m'] ?? null,
            'load_average_5m' => $validated['load_average_5m'] ?? null,
            'load_average_15m' => $validated['load_average_15m'] ?? null,
            'recorded_at' => now(),
        ]);

        // Update server's free disk
        if (isset($validated['disk_total_gb']) && isset($validated['disk_used_gb'])) {
            $server->update([
                'free_disk_gb' => $validated['disk_total_gb'] - $validated['disk_used_gb'],
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get commands for agent to execute.
     */
    public function getCommands(Request $request)
    {
        $validated = $request->validate([
            'agent_token' => 'required|string',
        ]);

        $server = Server::where('agent_token', $validated['agent_token'])->first();

        if (!$server) {
            return response()->json(['error' => 'Invalid agent token'], 401);
        }

        $server->update(['last_seen' => now()]);

        // For now, return empty commands
        // In future, this could queue commands for the agent
        return response()->json(['commands' => []]);
    }
}

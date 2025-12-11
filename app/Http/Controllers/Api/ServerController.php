<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\SshService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ServerController extends Controller
{
    public function __construct(
        private SshService $sshService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servers = Auth::user()->servers()->with('latestMetric')->get();
        
        return response()->json($servers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'ssh_user' => 'nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_private_key' => 'nullable|string',
            'ssh_public_key' => 'nullable|string',
        ]);

        $server = new Server($validated);
        $server->user_id = Auth::id();
        $server->ssh_user = $validated['ssh_user'] ?? 'root';
        $server->ssh_port = $validated['ssh_port'] ?? 22;
        $server->agent_token = Str::random(64);

        if (!empty($validated['ssh_private_key'])) {
            $server->setSshPrivateKey($validated['ssh_private_key']);
        }

        $server->save();

        return response()->json($server, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Server $server)
    {
        $this->authorize('view', $server);
        
        $server->load(['sites', 'databases', 'latestMetric']);
        
        return response()->json($server);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Server $server)
    {
        $this->authorize('update', $server);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'hostname' => 'sometimes|string|max:255',
            'ip_address' => 'sometimes|ip',
            'ssh_user' => 'sometimes|string|max:255',
            'ssh_port' => 'sometimes|integer|min:1|max:65535',
            'ssh_private_key' => 'sometimes|string',
            'ssh_public_key' => 'sometimes|string',
        ]);

        if (!empty($validated['ssh_private_key'])) {
            $server->setSshPrivateKey($validated['ssh_private_key']);
            unset($validated['ssh_private_key']);
        }

        $server->update($validated);

        return response()->json($server);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Server $server)
    {
        $this->authorize('delete', $server);
        
        $server->delete();

        return response()->json(null, 204);
    }

    /**
     * Test SSH connection.
     */
    public function testConnection(Server $server)
    {
        $this->authorize('view', $server);
        
        $connected = $this->sshService->testConnection($server);
        
        return response()->json(['connected' => $connected]);
    }
}

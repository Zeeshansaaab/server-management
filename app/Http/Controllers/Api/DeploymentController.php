<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Http\Request;

class DeploymentController extends Controller
{
    public function __construct(
        private DeploymentService $deploymentService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Deployment::with(['site.server']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        $deployments = $query->latest()->paginate(20);

        return response()->json($deployments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'commit_hash' => 'nullable|string|max:255',
        ]);

        $site = Site::findOrFail($validated['site_id']);
        $this->authorize('view', $site->server);

        $deployment = $this->deploymentService->deploy(
            $site,
            $validated['commit_hash'] ?? null
        );

        return response()->json($deployment->load('site'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Deployment $deployment)
    {
        $this->authorize('view', $deployment->site->server);
        
        $deployment->load(['site.server', 'logs']);
        
        return response()->json($deployment);
    }
}

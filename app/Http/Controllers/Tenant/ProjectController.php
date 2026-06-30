<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublishProjectRequest;
use App\Models\Tenant\Project;
use App\Services\ProjectService;
use Exception;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function store($tenant, Request $request)
    {
        // Typically executed by Issuer
        $request->validate([
            'title' => 'required|string',
            'funding_target' => 'required|integer|min:1',
            'lock_period_months' => 'required|integer|min:1',
        ]);

        $project = Project::create([
            'issuer_id' => 1, // Hardcoded for this test assuming logged in Issuer
            'title' => $request->title,
            'description' => $request->description,
            'funding_target' => $request->funding_target,
            'lock_period_months' => $request->lock_period_months,
            'minimum_investment' => $request->minimum_investment ?? 0,
            'status' => 'submitted',
        ]);

        return response()->json($project, 201);
    }

    public function publish($tenant, Project $project, PublishProjectRequest $request)
    {
        try {
            $publishedProject = $this->projectService->publishProject(
                $project,
                (float) $request->gross_return_rate,
                (float) $request->investor_return_rate
            );

            return response()->json([
                'message' => 'Project published successfully.',
                'project' => $publishedProject,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

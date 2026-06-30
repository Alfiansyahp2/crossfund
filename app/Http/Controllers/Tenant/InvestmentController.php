<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestRequest;
use App\Models\Tenant\Project;
use App\Services\InvestmentService;
use Exception;

class InvestmentController extends Controller
{
    protected InvestmentService $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

    public function invest(Project $project, InvestRequest $request)
    {
        $user = $request->user();

        try {
            $investment = $this->investmentService->invest(
                $user,
                $project,
                (int) $request->amount
            );

            return response()->json([
                'message' => 'Investment successful.',
                'investment' => $investment,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

<?php

namespace App\Services;

use App\Models\AgentTier;
use App\Models\Tenant\Project;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Publish a project and validate that commissions fit within the platform margin.
     * 
     * @param Project $project
     * @param float $grossReturnRate (e.g. 15.00 for 15%)
     * @param float $investorReturnRate (e.g. 7.00 for 7%)
     * @return Project
     * @throws Exception
     */
    public function publishProject(Project $project, float $grossReturnRate, float $investorReturnRate): Project
    {
        // 1. Validate Investor Return is <= 50% of Gross Return
        if ($investorReturnRate > (0.5 * $grossReturnRate)) {
            throw new Exception("Investor return rate cannot exceed 50% of the gross return rate.");
        }

        // 2. Calculate Platform Margin
        $platformMargin = $grossReturnRate - $investorReturnRate;

        // 3. Validate maximum theoretical commissions
        // Theoretical max = Referral Rate (assume global setting or hardcoded e.g. 2%) + Max Direct + Max Override
        $referralRate = 2.00; // In reality, fetch from SystemSetting
        
        $maxDirect = AgentTier::max('direct_commission_rate') ?? 0;
        $maxOverride = AgentTier::max('override_commission_rate') ?? 0;
        
        $totalMaxCommissions = $referralRate + $maxDirect + $maxOverride;

        if ($totalMaxCommissions > $platformMargin) {
            throw new Exception("Platform margin ({$platformMargin}%) is too low to cover the maximum possible commissions ({$totalMaxCommissions}%).");
        }

        // 4. Update and publish the project
        $project->gross_return_rate = $grossReturnRate;
        $project->investor_return_rate = $investorReturnRate;
        $project->status = 'published';
        $project->published_at = now();
        $project->save();

        return $project;
    }
}

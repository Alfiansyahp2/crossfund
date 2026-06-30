<?php

namespace Tests\Unit;

use App\Models\AgentTier;
use App\Models\Tenant\Project;
use App\Services\ProjectService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectService $projectService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectService = app(ProjectService::class);
        
        AgentTier::create(['name' => 'Bronze', 'direct_commission_rate' => 2.0, 'override_commission_rate' => 0.5]);
        AgentTier::create(['name' => 'Silver', 'direct_commission_rate' => 3.0, 'override_commission_rate' => 1.0]);
        AgentTier::create(['name' => 'Gold', 'direct_commission_rate' => 4.0, 'override_commission_rate' => 1.5]);
    }

    public function test_publish_fails_if_investor_rate_too_high()
    {
        $issuer = \App\Models\Tenant\Issuer::create([
            'name' => 'John 1',
            'company_name' => 'Test Issuer 1',
            'country' => 'Indonesia',
            'email' => 'issuer@test.com',
            'password' => bcrypt('password')
        ]);

        $project = Project::create([
            'issuer_id' => $issuer->id,
            'title' => 'Bad Project',
            'funding_target' => 1000000,
            'gross_return_rate' => 10.0,
            'investor_return_rate' => 6.0, // > 50% of 10.0
            'status' => 'draft',
            'lock_period_months' => 12
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Investor return rate cannot exceed 50% of the gross return rate.");

        $this->projectService->publishProject($project, 10.0, 6.0);
    }

    public function test_publish_fails_if_margins_cannot_cover_commissions()
    {
        // Max commission possible: Gold Direct (4.0) + Gold Override (1.5) = 5.5
        // Gross (10.0) - Investor (5.0) = Platform Margin (5.0)
        // 5.0 < 5.5 -> Fails!
        
        $issuer = \App\Models\Tenant\Issuer::create([
            'name' => 'John 2',
            'company_name' => 'Test Issuer 2',
            'country' => 'Indonesia',
            'email' => 'issuer2@test.com',
            'password' => bcrypt('password')
        ]);
        
        $project = Project::create([
            'issuer_id' => $issuer->id,
            'title' => 'Tight Margin Project',
            'funding_target' => 1000000,
            'gross_return_rate' => 10.0,
            'investor_return_rate' => 5.0,
            'status' => 'draft',
            'lock_period_months' => 12
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Platform margin (5%) is too low to cover the maximum possible commissions (7.5%).");

        $this->projectService->publishProject($project, 10.0, 5.0);
    }
    
    public function test_publish_success()
    {
        // Margin: 12 - 4 = 8.0. Covers 5.5 max commission.
        $issuer = \App\Models\Tenant\Issuer::create([
            'name' => 'John 3',
            'company_name' => 'Test Issuer 3',
            'country' => 'Indonesia',
            'email' => 'issuer3@test.com',
            'password' => bcrypt('password')
        ]);

        $project = Project::create([
            'issuer_id' => $issuer->id,
            'title' => 'Good Project',
            'funding_target' => 1000000,
            'gross_return_rate' => 12.0,
            'investor_return_rate' => 4.0,
            'status' => 'draft',
            'lock_period_months' => 12
        ]);

        $this->projectService->publishProject($project, 12.0, 4.0);

        $this->assertEquals('published', $project->fresh()->status);
    }
}

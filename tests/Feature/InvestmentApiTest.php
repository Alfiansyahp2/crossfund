<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Tenant\Project;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $idr = Currency::create(['name' => 'Rupiah', 'code' => 'IDR', 'decimals' => 0]);
        $usd = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'decimals' => 2]);
        
        ExchangeRate::create([
            'from_currency_id' => $usd->id,
            'to_currency_id' => $idr->id,
            'rate' => 15000,
            'effective_at' => now(),
        ]);
        
        // Tenant setup is aliased in TestCase
    }

    public function test_full_investment_flow_api()
    {
        $usd = Currency::where('code', 'USD')->first();
        
        $investor = User::factory()->create(['home_currency_id' => $usd->id, 'role' => 'investor']);
        Wallet::create(['user_id' => $investor->id, 'currency_id' => $usd->id, 'balance' => 1000]); // $1,000 USD
        
        $issuer = \App\Models\Tenant\Issuer::create([
            'name' => 'API John',
            'company_name' => 'Test Issuer API',
            'country' => 'Indonesia',
            'email' => 'issuer@test.com',
            'password' => bcrypt('password')
        ]);
        
        $project = Project::create([
            'issuer_id' => $issuer->id,
            'title' => 'API Project',
            'funding_target' => 150000000,
            'gross_return_rate' => 10.0,
            'investor_return_rate' => 5.0,
            'status' => 'published',
            'lock_period_months' => 12
        ]);

        $tenant = \App\Models\Tenant::create([
            'name' => 'Tenant 1',
            'subdomain' => 'tenant1',
            'status' => 'active',
            'country_code' => 'ID',
            'currency_id' => $usd->id,
            'db_name' => 'tenant_alpha_testing'
        ]);

        $url = route('tenant.invest', ['tenant' => 'tenant1', 'project' => $project->id]);
        $response = $this->actingAs($investor)->postJson($url, [
            'amount' => 15000000 // 15 Juta IDR = 1,000 USD
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Investment successful.');
        
        // Wallet should be 0
        $this->assertEquals(0, $investor->wallet->fresh()->balance);
        
        // Investment should exist
        $this->assertDatabaseHas('investments', [
            'user_id' => $investor->id,
            'project_id' => $project->id,
            'principal_amount' => 15000000
        ]);
        
        // Wallet Transaction should exist
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $investor->wallet->id,
            'type' => 'invest',
            'amount' => -100000
        ]);
    }
}

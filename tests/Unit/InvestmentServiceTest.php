<?php

namespace Tests\Unit;

use App\Models\AgentTier;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Tenant\Project;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CommissionService;
use App\Services\InvestmentService;
use App\Services\WalletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvestmentService $investmentService;
    protected User $investor;
    protected Project $project;
    protected Currency $usd;
    protected Currency $idr;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->investmentService = app(InvestmentService::class);

        // Setup Currencies
        $this->idr = Currency::create(['name' => 'Rupiah', 'code' => 'IDR', 'decimals' => 0]);
        $this->usd = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'decimals' => 2]);

        // Setup Rate (1 USD = 15000 IDR)
        ExchangeRate::create([
            'from_currency_id' => $this->usd->id,
            'to_currency_id' => $this->idr->id,
            'rate' => 15000,
            'effective_at' => now(),
        ]);

        // Setup Investor
        $this->investor = User::create([
            'name' => 'John',
            'email' => 'john@test.com',
            'password' => bcrypt('password'),
            'home_currency_id' => $this->usd->id,
            'role' => 'investor'
        ]);
        Wallet::create(['user_id' => $this->investor->id, 'currency_id' => $this->usd->id, 'balance' => 1000]); // $1,000.00 USD

        // Setup Project (Tenant DB - Currency IDR)
        $issuer = \App\Models\Tenant\Issuer::create([
            'name' => 'John Doe',
            'company_name' => 'Test Issuer',
            'country' => 'Indonesia',
            'email' => 'issuer@test.com',
            'password' => bcrypt('password')
        ]);
        
        $this->project = Project::create([
            'issuer_id' => $issuer->id,
            'title' => 'Test Project',
            'funding_target' => 150000000, // 150 Juta IDR
            'gross_return_rate' => 10.0,
            'investor_return_rate' => 5.0,
            'status' => 'published',
            'lock_period_months' => 12
        ]);
    }

    public function test_successful_investment_with_currency_conversion()
    {
        // Act: Invest 15,000,000 IDR
        $investmentAmountIdr = 15000000;
        $investment = $this->investmentService->invest($this->investor, $this->project, $investmentAmountIdr);

        // Assert Wallet Deduction (15,000,000 IDR / 15,000 = 1,000 USD = 100,000 cents)
        $this->investor->wallet->refresh();
        $this->assertEquals(0, $this->investor->wallet->balance);

        // Assert Investment Created
        $this->assertNotNull($investment);
        $this->assertEquals(15000000, $investment->principal_amount);
        $this->assertEquals(15000, $investment->exchange_rate_used);
        
        // Assert Returns (5% of 15,000,000 = 750,000)
        $this->assertEquals(750000, $investment->return_amount);
    }

    public function test_investment_fails_on_insufficient_balance()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient wallet balance.");

        // Attempt to invest 30,000,000 IDR (requires 2,000 USD, but wallet only has 1,000 USD)
        $this->investmentService->invest($this->investor, $this->project, 30000000);
    }

    public function test_investment_fails_if_project_finished()
    {
        $this->project->update(['status' => 'finished']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Project is not open for investments.");

        $this->investmentService->invest($this->investor, $this->project, 15000000);
    }

    public function test_investment_fails_if_exchange_rate_missing()
    {
        ExchangeRate::truncate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No active exchange rate found");

        $this->investmentService->invest($this->investor, $this->project, 15000000);
    }
}

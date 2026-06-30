<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Tenant\WalletTransaction;
use App\Services\WalletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
        
        $currency = \App\Models\Currency::create(['name' => 'USD', 'code' => 'USD', 'decimals' => 2]);

        $user = User::create([
            'name' => 'John',
            'email' => 'john@test.com',
            'password' => bcrypt('password'),
            'home_currency_id' => $currency->id,
            'role' => 'investor'
        ]);
        
        $this->wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 50000 // 500.00
        ]);
    }

    public function test_topup_increases_balance_and_creates_transaction()
    {
        $this->walletService->recordTransaction($this->wallet, 'topup', 10000); // Add 100.00

        $this->wallet->refresh();
        $this->assertEquals(60000, $this->wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'topup',
            'amount' => 10000,
            'balance_before' => 50000,
            'balance_after' => 60000,
        ]);
    }

    public function test_invest_decreases_balance_and_creates_transaction()
    {
        $this->walletService->recordTransaction($this->wallet, 'invest', -20000); // Deduct 200.00

        $this->wallet->refresh();
        $this->assertEquals(30000, $this->wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'invest',
            'amount' => -20000,
            'balance_before' => 50000,
            'balance_after' => 30000,
        ]);
    }

    public function test_overdraft_prevention_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient wallet balance.");

        // Attempt to deduct 600.00 from 500.00 balance
        $this->walletService->recordTransaction($this->wallet, 'invest', -60000);
        
        // Assert balance hasn't changed
        $this->wallet->refresh();
        $this->assertEquals(50000, $this->wallet->balance);
    }
}

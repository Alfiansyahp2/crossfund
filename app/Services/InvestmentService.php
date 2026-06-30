<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\Tenant\Investment;
use App\Models\Tenant\Project;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class InvestmentService
{
    protected WalletService $walletService;
    protected CommissionService $commissionService;

    public function __construct(WalletService $walletService, CommissionService $commissionService)
    {
        $this->walletService = $walletService;
        $this->commissionService = $commissionService;
    }

    /**
     * Process an investment request.
     * Converts Home Currency to Tenant Currency, deducts wallet, and distributes commissions.
     *
     * @param User $investor
     * @param Project $project
     * @param int $amountInTenantCurrency
     * @return Investment
     * @throws Exception
     */
    public function invest(User $investor, Project $project, int $amountInTenantCurrency): Investment
    {
        if ($project->status !== 'published') {
            throw new Exception("Project is not open for investments.");
        }

        if ($amountInTenantCurrency < $project->minimum_investment) {
            throw new Exception("Investment amount is below the minimum required.");
        }

        // 1. Get Exchange Rate (Home Currency -> Tenant Currency)
        $tenantCurrencyId = $project->issuer->tenant->currency_id ?? null; // Ideally fetch tenant currency cleanly
        // Assuming we look up the latest rate:
        $exchangeRate = ExchangeRate::where('from_currency_id', $investor->home_currency_id)
                                    // ->where('to_currency_id', $tenantCurrencyId) // Pseudo-code depending on exact relation
                                    ->latest('effective_at')
                                    ->first();

        if (!$exchangeRate) {
            throw new Exception("No active exchange rate found.");
        }

        $rateValue = $exchangeRate->rate;

        // 2. Calculate Cost in Home Currency
        // Formula: TenantAmount / Rate = HomeAmount
        // e.g., if Rate is 15000 IDR/USD, and investing 15000 IDR, cost is 1 USD.
        $costInHomeCurrency = (int) ceil($amountInTenantCurrency / $rateValue);

        // We wrap the central DB operations in a transaction
        return DB::transaction(function () use ($investor, $project, $amountInTenantCurrency, $costInHomeCurrency, $rateValue) {
            
            // 3. Deduct Investor Wallet (Central DB)
            $this->walletService->recordTransaction(
                $investor->wallet, 
                'invest', 
                -$costInHomeCurrency, 
                "PROJ-{$project->id}", 
                $rateValue
            );

            // 4. Record Investment (Tenant DB)
            // Calculate exact return amount
            $returnAmount = (int) ($amountInTenantCurrency * ($project->investor_return_rate / 100));

            $investment = Investment::create([
                'project_id' => $project->id,
                'user_id' => $investor->id,
                'principal_amount' => $amountInTenantCurrency,
                'return_amount' => $returnAmount,
                'exchange_rate_used' => $rateValue,
                'status' => 'active',
                'locked_until' => now()->addMonths($project->lock_period_months),
            ]);

            // Update project funding
            $project->increment('current_funding', $amountInTenantCurrency);
            if ($project->current_funding >= $project->funding_target) {
                $project->update(['status' => 'funded']);
            }

            // 5. Distribute Commissions (Tenant DB + Central DB)
            $this->commissionService->distributeCommissions($investment, $rateValue);

            return $investment;
        });
    }
}

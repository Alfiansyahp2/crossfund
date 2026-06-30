<?php

namespace App\Services;

use App\Models\Tenant\Commission;
use App\Models\Tenant\Investment;
use App\Models\User;

class CommissionService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Distribute referral, direct, and override commissions for an investment.
     * 
     * @param Investment $investment
     * @param float $exchangeRateUsed (Rate used during investment, or fetch a new one for payout)
     */
    public function distributeCommissions(Investment $investment, float $exchangeRateUsed): void
    {
        $investor = $investment->user;
        $principal = $investment->principal_amount;

        // Base referral rate (could be fetched from SystemSetting)
        $referralRate = 2.00;

        // 1. Upline / Referrer
        $upline = $investor->upline;
        if (!$upline) {
            return; // No one referred this investor
        }

        // Pay Referral Commission
        $this->processPayout($investment, $upline, 'referral', $referralRate, $principal, $exchangeRateUsed);

        // 2. Direct Agent Commission
        // If the upline is an agent, they get the direct commission from their tier
        if ($upline->role === 'agent' && $upline->agentTier) {
            $directRate = (float) $upline->agentTier->direct_commission_rate;
            if ($directRate > 0) {
                $this->processPayout($investment, $upline, 'direct_agent', $directRate, $principal, $exchangeRateUsed);
            }

            // 3. Override Agent Commission
            // The agent's upline (the override agent) gets the override commission
            $overrideAgent = $upline->upline;
            if ($overrideAgent && $overrideAgent->role === 'agent' && $overrideAgent->agentTier) {
                $overrideRate = (float) $overrideAgent->agentTier->override_commission_rate;
                if ($overrideRate > 0) {
                    $this->processPayout($investment, $overrideAgent, 'override_agent', $overrideRate, $principal, $exchangeRateUsed);
                }
            }
        }
    }

    /**
     * Calculate and save commission, then credit the wallet.
     */
    private function processPayout(Investment $investment, User $recipient, string $type, float $rate, int $principal, float $exchangeRateUsed): void
    {
        // Calculate amount in Tenant Currency
        $commissionInTenantCurrency = (int) ($principal * ($rate / 100));

        // Convert to Home Currency for payout
        // Note: For actual payout, we divide or multiply based on from/to direction.
        $commissionInHomeCurrency = (int) ceil($commissionInTenantCurrency / $exchangeRateUsed);

        // Record in Tenant DB
        Commission::create([
            'project_id' => $investment->project_id,
            'investment_id' => $investment->id,
            'recipient_user_id' => $recipient->id,
            'commission_type' => $type,
            'commission_rate_used' => $rate,
            'exchange_rate_used' => $exchangeRateUsed,
            'amount' => $commissionInHomeCurrency,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Credit Wallet in Central DB
        if ($recipient->wallet) {
            $this->walletService->recordTransaction(
                $recipient->wallet,
                'commission',
                $commissionInHomeCurrency,
                "COMM-INV-{$investment->id}",
                $exchangeRateUsed
            );
        }
    }
}

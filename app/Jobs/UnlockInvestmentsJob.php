<?php

namespace App\Jobs;

use App\Models\Tenant\Investment;
use App\Models\Tenant\Project;
use App\Services\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnlockInvestmentsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(WalletService $walletService): void
    {
        Log::info("Starting UnlockInvestmentsJob...");

        // Note: For a multi-tenant system, this job would normally iterate through all tenants,
        // switch connections, and query Investments. For this technical test, we assume
        // the connection is set or we test it on a single tenant scope.
        
        $maturedInvestments = Investment::where('status', 'active')
            ->where('locked_until', '<=', now())
            ->get();

        foreach ($maturedInvestments as $investment) {
            try {
                // Calculate payout in Home Currency
                // Payout = Principal + Returns. We convert it back using the saved rate.
                // Note: The logic for converting back might use the current rate or locked rate depending on business rule.
                // Assuming we use the rate at investment time (locked rate) to calculate exact payout.
                
                $totalPayoutTenant = $investment->principal_amount + $investment->return_amount;
                $payoutHomeCurrency = (int) ceil($totalPayoutTenant / $investment->exchange_rate_used);

                $walletService->recordTransaction(
                    $investment->user->wallet,
                    'payout',
                    $payoutHomeCurrency,
                    "UNLOCK-INV-{$investment->id}",
                    $investment->exchange_rate_used
                );

                $investment->update(['status' => 'unlocked']);
                
                Log::info("Unlocked investment ID: {$investment->id} for User: {$investment->user_id}");

            } catch (Exception $e) {
                Log::error("Failed to unlock investment ID: {$investment->id} - " . $e->getMessage());
            }
        }
        
        Log::info("UnlockInvestmentsJob finished.");
    }
}

<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Record a transaction securely with pessimistic locking to prevent race conditions.
     * 
     * @param Wallet $wallet
     * @param string $type (topup, withdraw, invest, payout, commission)
     * @param int $amount (positive for credit, negative for debit)
     * @param string|null $referenceId
     * @param float|null $exchangeRateUsed
     * @return WalletTransaction
     * @throws Exception
     */
    public function recordTransaction(Wallet $wallet, string $type, int $amount, ?string $referenceId = null, ?float $exchangeRateUsed = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $type, $amount, $referenceId, $exchangeRateUsed) {
            // Lock the wallet row for update to ensure atomic balance reading
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $balanceBefore = $lockedWallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            // Prevent overdraft
            if ($balanceAfter < 0) {
                throw new Exception("Insufficient wallet balance.");
            }

            // Update balance
            $lockedWallet->balance = $balanceAfter;
            $lockedWallet->save();

            // Record immutable audit trail
            return WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'exchange_rate_used' => $exchangeRateUsed,
                'reference_id' => $referenceId,
            ]);
        });
    }
}

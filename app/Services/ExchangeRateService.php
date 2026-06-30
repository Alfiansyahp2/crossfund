<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ExchangeRateService
{
    /**
     * Process incoming exchange rates payload and save to the database.
     * 
     * Payload expected:
     * {
     *   "source": "x-rates",
     *   "scraped_at": "2026-07-01T10:00:00Z",
     *   "rates": [
     *     { "from": "USD", "to": "IDR", "rate": 16250.25 },
     *     ...
     *   ]
     * }
     */
    public function syncRates(array $payload): void
    {
        $source = $payload['source'] ?? 'Unknown';
        $scrapedAt = isset($payload['scraped_at']) ? Carbon::parse($payload['scraped_at']) : now();
        $rates = $payload['rates'] ?? [];

        if (empty($rates)) {
            throw new Exception("No rates provided in the payload.");
        }

        DB::transaction(function () use ($rates, $scrapedAt) {
            foreach ($rates as $rateData) {
                $fromCode = $rateData['from'];
                $toCode = $rateData['to'];
                $rateValue = $rateData['rate'];

                $fromCurrency = Currency::where('code', $fromCode)->first();
                $toCurrency = Currency::where('code', $toCode)->first();

                if ($fromCurrency && $toCurrency) {
                    ExchangeRate::create([
                        'from_currency_id' => $fromCurrency->id,
                        'to_currency_id' => $toCurrency->id,
                        'rate' => $rateValue,
                        'effective_at' => $scrapedAt,
                    ]);
                }
            }
        });
    }
}

<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExchangeRateController extends Controller
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function sync(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'scraped_at' => 'required|date',
            'rates' => 'required|array|min:1',
            'rates.*.from' => 'required|string|size:3',
            'rates.*.to' => 'required|string|size:3',
            'rates.*.rate' => 'required|numeric|min:0',
        ]);

        try {
            $this->exchangeRateService->syncRates($request->all());
            
            Log::info("Exchange rates synced successfully from {$request->source}.");

            return response()->json([
                'message' => 'Exchange rates synchronized successfully.',
            ], 200);

        } catch (Exception $e) {
            Log::error("Failed to sync exchange rates: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

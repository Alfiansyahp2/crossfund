<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\WalletTopup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'payment_proof' => 'nullable|string', // In reality, this would be a file upload validation
        ]);

        $user = $request->user();

        $topup = WalletTopup::create([
            'reference_number' => 'TOPUP-' . now()->format('Ymd') . '-' . Str::random(5),
            'user_id' => $user->id,
            'amount' => $request->amount,
            'status' => 'pending',
            'payment_proof' => $request->payment_proof,
        ]);

        return response()->json([
            'message' => 'Topup request submitted successfully. Awaiting admin approval.',
            'topup' => $topup,
        ]);
    }
}

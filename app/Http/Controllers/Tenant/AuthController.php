<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Admin;
use App\Models\Tenant\Issuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:admin,issuer',
        ]);

        $user = null;
        if ($request->role === 'admin') {
            $user = Admin::where('email', $request->email)->first();
        } else {
            $user = Issuer::where('email', $request->email)->first();
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Note: For Sanctum to work on Tenant models, they need to use HasApiTokens trait.
        return response()->json([
            'token' => $user->createToken('tenant-token')->plainTextToken,
            'user' => $user,
            'role' => $request->role,
        ]);
    }
}

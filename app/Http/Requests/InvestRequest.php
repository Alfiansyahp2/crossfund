<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Must be logged in
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'], // Minimum validation happens in Service
        ];
    }
}

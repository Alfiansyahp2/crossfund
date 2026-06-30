<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'gross_return_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'investor_return_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}

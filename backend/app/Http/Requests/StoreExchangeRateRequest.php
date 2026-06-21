<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_currency_code' => 'required|string|exists:currencies,code',
            'to_currency_code' => 'required|string|exists:currencies,code|different:from_currency_code',
            'rate' => 'required|numeric|gt:0',
            'effective_date' => 'nullable|date',
            'source' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_currency_code' => 'string|exists:currencies,code',
            'to_currency_code' => 'string|exists:currencies,code|different:from_currency_code',
            'rate' => 'numeric|gt:0',
            'effective_date' => 'nullable|date',
            'source' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}

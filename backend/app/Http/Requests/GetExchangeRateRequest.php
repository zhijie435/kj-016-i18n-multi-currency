<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_currency_code' => 'required|string',
            'to_currency_code' => 'required|string',
            'date' => 'nullable|date',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeRateMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency_codes' => 'required|array',
            'currency_codes.*' => 'string',
            'date' => 'nullable|date',
        ];
    }
}

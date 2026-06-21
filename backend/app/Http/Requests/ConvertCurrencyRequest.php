<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0',
            'from_currency_code' => 'required|string',
            'to_currency_code' => 'required|string',
            'date' => 'nullable|date',
        ];
    }
}

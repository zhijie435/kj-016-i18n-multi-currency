<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:16|unique:currencies,code',
            'name' => 'required|string|max:64',
            'symbol' => 'nullable|string|max:16',
            'decimals' => 'nullable|integer|min:0|max:8',
            'is_enabled' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}

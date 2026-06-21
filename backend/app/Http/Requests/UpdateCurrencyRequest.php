<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currencyId = $this->route('id') ?? 'NULL';
        return [
            'code' => 'string|max:16|unique:currencies,code,' . $currencyId,
            'name' => 'string|max:64',
            'symbol' => 'nullable|string|max:16',
            'decimals' => 'nullable|integer|min:0|max:8',
            'is_enabled' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}

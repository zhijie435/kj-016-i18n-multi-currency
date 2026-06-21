<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:64|unique:channels,code',
            'name' => 'required|string|max:128',
            'description' => 'nullable|string',
            'locale_code' => 'nullable|string|exists:locales,code',
            'currency_code' => 'nullable|string|max:16',
            'currency_symbol' => 'nullable|string|max:16',
            'currency_decimals' => 'nullable|integer|min:0|max:8',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:16|unique:locales,code',
            'name' => 'required|string|max:64',
            'native_name' => 'required|string|max:64',
            'flag' => 'nullable|string|max:16',
            'element_locale' => 'nullable|string|max:32',
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}

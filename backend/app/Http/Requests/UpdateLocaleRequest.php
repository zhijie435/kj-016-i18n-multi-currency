<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $localeId = $this->route('id') ?? 'NULL';
        return [
            'code' => 'string|max:16|unique:locales,code,' . $localeId,
            'name' => 'string|max:64',
            'native_name' => 'string|max:64',
            'flag' => 'nullable|string|max:16',
            'element_locale' => 'nullable|string|max:32',
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}

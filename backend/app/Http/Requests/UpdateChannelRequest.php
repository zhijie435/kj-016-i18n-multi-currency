<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channelId = $this->route('id') ?? 'NULL';
        return [
            'code' => 'string|max:64|unique:channels,code,' . $channelId,
            'name' => 'string|max:128',
            'description' => 'nullable|string',
            'locale_code' => 'nullable|string',
            'currency_code' => 'nullable|string|max:16',
            'currency_symbol' => 'nullable|string|max:16',
            'currency_decimals' => 'nullable|integer|min:0|max:8',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}

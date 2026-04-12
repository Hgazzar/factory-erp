<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:units,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'base_unit_id' => ['nullable', 'exists:units,id'],
            'conversion_factor' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) auth()->id();
        $item = $this->route('item');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'code')
                    ->where('user_id', $userId)
                    ->ignore($item),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items', 'barcode')
                    ->where('user_id', $userId)
                    ->ignore($item),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['required', 'exists:units,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'in:'.implode(',', Item::typeValues())],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'material_type' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'الرمز مُستخدم مسبقاً. اختر رمزاً فريداً.',
            'barcode.unique' => 'الباركود مُستخدم مسبقاً. غيّره أو استخدم زر التوليد.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) auth()->id();

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'code')->where('user_id', $userId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items', 'barcode')->where('user_id', $userId),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['required', 'exists:units,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where('user_id', $userId),
            ],
            'type' => ['required', 'in:'.implode(',', Item::typeValues())],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'material_type' => ['nullable', 'string', 'max:100'],
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

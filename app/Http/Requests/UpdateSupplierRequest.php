<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) auth()->id();
        $supplier = $this->route('supplier');

        return [
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'mobile')->where('user_id', $userId)->ignore($supplier->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->where('user_id', $userId)->ignore($supplier->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'supplier_type' => ['nullable', 'string', 'max:50'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'tax_number' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'tax_number')->where('user_id', $userId)->ignore($supplier->id)],
            'commercial_register' => ['nullable', 'string', 'max:100', Rule::unique('suppliers', 'commercial_register')->where('user_id', $userId)->ignore($supplier->id)],
            'currency' => ['nullable', 'string', 'max:5'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'البريد الإلكتروني يجب أن يكون بصيغة صحيحة.',
            'email.unique' => 'البريد الإلكتروني مسجّل لمورد آخر.',
            'tax_number.unique' => 'الرقم الضريبي مسجّل لمورد آخر.',
            'commercial_register.unique' => 'السجل التجاري مسجّل لمورد آخر.',
            'mobile.unique' => 'رقم الجوال مسجّل لمورد آخر.',
        ];
    }
}

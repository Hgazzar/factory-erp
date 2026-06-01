<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantUserId = $this->tenantUserId();

        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $tenantUserId)],
            'settlement_type' => ['required', 'in:on_account,cash'],
            'reference' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $tenantUserId)],
            'lines.*.warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $tenantUserId)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.purchase_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'المورد مطلوب.',
            'lines.required' => 'يجب إضافة بند واحد على الأقل.',
        ];
    }

    private function tenantUserId(): int
    {
        return app(TenantContext::class)->resolveTenantUserId()
            ?? (int) auth()->id();
    }
}

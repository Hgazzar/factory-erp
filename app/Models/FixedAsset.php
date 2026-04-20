<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code',
        'name',
        'name_ar',
        'fixed_asset_category_id',
        'cost_center_id',
        'ledger_account_id',
        'payment_method',
        'bank_account_id',
        'journal_entry_id',
        'source_payment_id',
        'category',
        'location',
        'description',
        'acquisition_date',
        'acquisition_cost',
        'book_value',
        'depreciation_method',
        'useful_life_years',
        'useful_life_months',
        'depreciation_start_date',
        'salvage_value',
        'serial_number',
        'model',
        'manufacturer',
        'warranty_end_date',
        'insurance_document',
        'insurance_end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'book_value' => 'decimal:2',
            'depreciation_start_date' => 'date',
            'salvage_value' => 'decimal:2',
            'warranty_end_date' => 'date',
            'insurance_end_date' => 'date',
        ];
    }

    public function fixedAssetCategory(): BelongsTo
    {
        return $this->belongsTo(FixedAssetCategory::class, 'fixed_asset_category_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function getCalculatedBookValueAttribute(): float
    {
        $acquisitionCost = (float) ($this->acquisition_cost ?? 0);
        $salvageValue = (float) ($this->salvage_value ?? 0);

        if ($this->depreciation_method !== 'straightline') {
            return round((float) ($this->book_value ?? $acquisitionCost), 2);
        }

        if (! $this->depreciation_start_date) {
            return round($acquisitionCost, 2);
        }

        $totalMonths = ((int) ($this->useful_life_years ?? 0) * 12) + (int) ($this->useful_life_months ?? 0);
        if ($totalMonths <= 0) {
            return round($acquisitionCost, 2);
        }

        $depreciableAmount = max(0, $acquisitionCost - $salvageValue);
        if ($depreciableAmount <= 0) {
            return round($acquisitionCost, 2);
        }

        $startDate = Carbon::parse($this->depreciation_start_date)->startOfDay();
        $today = now()->startOfDay();
        if ($today->lt($startDate)) {
            return round($acquisitionCost, 2);
        }

        $elapsedMonths = min($totalMonths, $startDate->diffInMonths($today));
        $monthlyDepreciation = $depreciableAmount / $totalMonths;

        $currentValue = $acquisitionCost - ($elapsedMonths * $monthlyDepreciation);
        $currentValue = max($salvageValue, min($acquisitionCost, $currentValue));

        return round($currentValue, 2);
    }
}

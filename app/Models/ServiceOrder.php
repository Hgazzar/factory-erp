<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    public const TYPE_INSTALL = 'install';

    public const TYPE_MAINTENANCE = 'maintenance';

    public const TYPE_REPAIR = 'repair';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_OPEN = 'open';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference_number',
        'service_type',
        'priority',
        'status',
        'assigned_technician_id',
        'executed_at',
        'sales_order_id',
        'delivery_order_id',
        'installed_asset_id',
        'customer_id',
        'warehouse_id',
        'is_paid_service',
        'outside_warranty',
        'labor_amount',
        'sales_invoice_id',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'date',
            'is_paid_service' => 'boolean',
            'outside_warranty' => 'boolean',
            'labor_amount' => 'decimal:4',
        ];
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function installedAsset(): BelongsTo
    {
        return $this->belongsTo(InstalledAsset::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ServicePart::class);
    }

    public static function generateReferenceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'SRV-'.$year.'-';
        $last = static::query()
            ->where('reference_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('reference_number');
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', (string) $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * إنشاء فاتورة مبيعات مسودة (بدون قيد محاسبي) للخدمات المدفوعة.
     */
    public function createDraftInvoiceIfNeeded(): ?SalesInvoice
    {
        if (! $this->is_paid_service || $this->sales_invoice_id) {
            return null;
        }

        if (! $this->customer_id || ! $this->warehouse_id) {
            return null;
        }

        $this->loadMissing('parts.item');

        $lines = [];
        foreach ($this->parts as $part) {
            $item = $part->item;
            if (! $item) {
                continue;
            }
            $unitPrice = (float) ($item->selling_price ?? 0) > 0
                ? (float) $item->selling_price
                : (float) $part->unit_cost;
            $qty = (float) $part->quantity;
            $lineTotal = round($qty * $unitPrice, 4);
            if ($lineTotal <= 0) {
                continue;
            }
            $lines[] = [
                'item_id' => $item->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        $labor = (float) ($this->labor_amount ?? 0);
        if ($labor > 0.0001) {
            $laborItem = Item::query()
                ->where('type', Item::TYPE_SERVICE)
                ->orderBy('id')
                ->first();
            if ($laborItem) {
                $lines[] = [
                    'item_id' => $laborItem->id,
                    'quantity' => 1,
                    'unit_price' => $labor,
                    'line_total' => round($labor, 4),
                ];
            }
        }

        if ($lines === []) {
            return null;
        }

        $grandTotal = round(array_sum(array_column($lines, 'line_total')), 4);

        $invoice = SalesInvoice::query()->create([
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'service_order_id' => $this->id,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'reference' => $this->reference_number,
            'notes' => 'مسودة فاتورة تلقائية من طلب الخدمة '.$this->reference_number,
            'internal_notes' => 'service_order_id:'.$this->id,
            'payment_method' => 'credit',
            'invoice_status' => 'draft',
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => $grandTotal,
            'paid_amount' => 0,
            'journal_entry_id' => null,
        ]);

        foreach ($lines as $row) {
            $invoice->items()->create($row);
        }

        $this->sales_invoice_id = $invoice->id;
        $this->save();

        return $invoice;
    }

    public static function applyWarrantyRules(array &$data): void
    {
        $type = $data['service_type'] ?? '';
        if (! in_array($type, [self::TYPE_MAINTENANCE, self::TYPE_REPAIR], true)) {
            return;
        }

        if (empty($data['installed_asset_id'])) {
            $data['is_paid_service'] = true;

            return;
        }

        $asset = InstalledAsset::query()->find($data['installed_asset_id']);
        if (! $asset || ! $asset->warranty_end) {
            $data['is_paid_service'] = true;

            return;
        }

        if (Carbon::parse($asset->warranty_end)->isPast()) {
            $data['is_paid_service'] = true;
            $data['outside_warranty'] = true;
        } else {
            $data['is_paid_service'] = false;
            $data['outside_warranty'] = false;
        }
    }
}

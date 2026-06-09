<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustodyReturn;
use App\Models\Fleet\FleetCustodyReturnLine;
use App\Models\Fleet\FleetProduct;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class FleetCustodyReturnService
{
    public function __construct(
        private readonly FleetCustodyBalanceService $balances,
    ) {}

    /**
     * @param  list<array{product_id: int, quantity: float}>  $lines
     */
    public function createDraft(int $tenantUserId, int $agentId, string $returnedOn, array $lines, ?string $notes = null, ?int $recordedBy = null): FleetCustodyReturn
    {
        $this->assertAgent($tenantUserId, $agentId);
        $normalizedLines = $this->normalizeLines($tenantUserId, $agentId, $lines, validateBalance: false);

        return DB::transaction(function () use ($tenantUserId, $agentId, $returnedOn, $normalizedLines, $notes, $recordedBy): FleetCustodyReturn {
            $return = FleetCustodyReturn::query()->create([
                'user_id' => $tenantUserId,
                'agent_id' => $agentId,
                'return_number' => $this->nextReturnNumber($tenantUserId),
                'returned_on' => $returnedOn,
                'status' => FleetCustodyReturn::STATUS_DRAFT,
                'notes' => $this->nullable($notes),
                'recorded_by' => $recordedBy,
            ]);

            $this->syncLines($return, $tenantUserId, $normalizedLines);

            return $return->fresh(['agent:id,name', 'lines.product:id,name,sku']);
        });
    }

    public function confirm(FleetCustodyReturn $return, int $tenantUserId): FleetCustodyReturn
    {
        $this->assertOwned($return, $tenantUserId);

        if ($return->status !== FleetCustodyReturn::STATUS_DRAFT) {
            throw new InvalidArgumentException('يمكن تأكيد مسودة المرتجع فقط.');
        }

        $return->load('lines');
        if ($return->lines->isEmpty()) {
            throw new InvalidArgumentException('أضف صنفاً واحداً على الأقل قبل تأكيد المرتجع.');
        }

        foreach ($return->lines as $line) {
            $available = $this->balances->availableQuantity($tenantUserId, (int) $return->agent_id, (int) $line->product_id);
            if ((float) $line->quantity > $available + 0.0001) {
                $productName = $line->product?->name ?? 'الصنف';
                throw new InvalidArgumentException("الكمية المرتجعة لـ «{$productName}» تتجاوز رصيد العهدة ({$available}).");
            }
        }

        $return->update([
            'status' => FleetCustodyReturn::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return $return->fresh(['agent:id,name', 'lines.product:id,name,sku']);
    }

    public function void(FleetCustodyReturn $return, int $tenantUserId): FleetCustodyReturn
    {
        $this->assertOwned($return, $tenantUserId);

        if ($return->status === FleetCustodyReturn::STATUS_VOID) {
            throw new InvalidArgumentException('سند المرتجع ملغى بالفعل.');
        }

        $return->update(['status' => FleetCustodyReturn::STATUS_VOID]);

        return $return->fresh();
    }

    private function nextReturnNumber(int $tenantUserId): string
    {
        $last = FleetCustodyReturn::query()
            ->where('user_id', $tenantUserId)
            ->orderByDesc('id')
            ->value('return_number');

        $seq = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('FL-RET-%05d', $seq);
    }

    /**
     * @param  list<array{product_id: int, quantity: float, unit_price: float}>  $lines
     */
    private function syncLines(FleetCustodyReturn $return, int $tenantUserId, array $lines): void
    {
        $return->lines()->delete();

        foreach ($lines as $line) {
            FleetCustodyReturnLine::query()->create([
                'user_id' => $tenantUserId,
                'return_id' => $return->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
            ]);
        }
    }

    /**
     * @param  list<array{product_id?: mixed, quantity?: mixed}>  $lines
     * @return list<array{product_id: int, quantity: float, unit_price: float}>
     */
    private function normalizeLines(int $tenantUserId, int $agentId, array $lines, bool $validateBalance): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = round((float) ($line['quantity'] ?? 0), 4);

            if ($productId < 1 || $qty <= 0) {
                continue;
            }

            $product = FleetProduct::query()
                ->where('user_id', $tenantUserId)
                ->where('id', $productId)
                ->where('is_active', true)
                ->first(['id', 'name', 'sale_price']);

            if ($product === null) {
                throw new InvalidArgumentException('أحد الأصناف غير صالح أو غير نشط.');
            }

            if ($validateBalance) {
                $available = $this->balances->availableQuantity($tenantUserId, $agentId, $productId);
                if ($qty > $available + 0.0001) {
                    throw new InvalidArgumentException("الكمية المرتجعة لـ «{$product->name}» تتجاوز رصيد العهدة ({$available}).");
                }
            }

            $normalized[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'unit_price' => round((float) $product->sale_price, 4),
            ];
        }

        if ($normalized === []) {
            throw new InvalidArgumentException('أضف صنفاً واحداً على الأقل بكمية أكبر من صفر.');
        }

        return $normalized;
    }

    private function assertAgent(int $tenantUserId, int $agentId): void
    {
        $exists = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('id', $agentId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('المندوب غير موجود أو غير نشط.');
        }
    }

    private function assertOwned(FleetCustodyReturn $return, int $tenantUserId): void
    {
        if ((int) $return->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('سند المرتجع غير تابع لهذا الحساب.');
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}

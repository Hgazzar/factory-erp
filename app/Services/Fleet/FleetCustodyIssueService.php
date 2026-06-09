<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustodyIssue;
use App\Models\Fleet\FleetCustodyIssueLine;
use App\Models\Fleet\FleetProduct;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class FleetCustodyIssueService
{
    /**
     * @param  list<array{product_id: int, quantity: float}>  $lines
     */
    public function createDraft(int $tenantUserId, int $agentId, string $issuedOn, array $lines, ?string $notes = null, ?int $recordedBy = null): FleetCustodyIssue
    {
        $this->assertAgent($tenantUserId, $agentId);
        $normalizedLines = $this->normalizeLines($tenantUserId, $lines);

        return DB::transaction(function () use ($tenantUserId, $agentId, $issuedOn, $normalizedLines, $notes, $recordedBy): FleetCustodyIssue {
            $issue = FleetCustodyIssue::query()->create([
                'user_id' => $tenantUserId,
                'agent_id' => $agentId,
                'issue_number' => $this->nextIssueNumber($tenantUserId),
                'issued_on' => $issuedOn,
                'status' => FleetCustodyIssue::STATUS_DRAFT,
                'notes' => $this->nullable($notes),
                'recorded_by' => $recordedBy,
            ]);

            $this->syncLines($issue, $tenantUserId, $normalizedLines);

            return $issue->fresh(['agent:id,name', 'lines.product:id,name,sku']);
        });
    }

    public function confirm(FleetCustodyIssue $issue, int $tenantUserId): FleetCustodyIssue
    {
        $this->assertOwned($issue, $tenantUserId);

        if ($issue->status !== FleetCustodyIssue::STATUS_DRAFT) {
            throw new InvalidArgumentException('يمكن تأكيد مسودة العهدة فقط.');
        }

        if ($issue->lines()->count() < 1) {
            throw new InvalidArgumentException('أضف صنفاً واحداً على الأقل قبل تأكيد العهدة.');
        }

        $issue->update([
            'status' => FleetCustodyIssue::STATUS_ISSUED,
            'confirmed_at' => now(),
        ]);

        return $issue->fresh(['agent:id,name', 'lines.product:id,name,sku']);
    }

    public function void(FleetCustodyIssue $issue, int $tenantUserId): FleetCustodyIssue
    {
        $this->assertOwned($issue, $tenantUserId);

        if ($issue->status === FleetCustodyIssue::STATUS_VOID) {
            throw new InvalidArgumentException('سند العهدة ملغى بالفعل.');
        }

        $issue->update(['status' => FleetCustodyIssue::STATUS_VOID]);

        return $issue->fresh();
    }

    private function nextIssueNumber(int $tenantUserId): string
    {
        $last = FleetCustodyIssue::query()
            ->where('user_id', $tenantUserId)
            ->orderByDesc('id')
            ->value('issue_number');

        $seq = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('FL-CUS-%05d', $seq);
    }

    /**
     * @param  list<array{product_id: int, quantity: float}>  $lines
     */
    private function syncLines(FleetCustodyIssue $issue, int $tenantUserId, array $lines): void
    {
        $issue->lines()->delete();

        foreach ($lines as $line) {
            $product = FleetProduct::query()
                ->where('user_id', $tenantUserId)
                ->where('id', $line['product_id'])
                ->where('is_active', true)
                ->first();

            if ($product === null) {
                continue;
            }

            FleetCustodyIssueLine::query()->create([
                'user_id' => $tenantUserId,
                'issue_id' => $issue->id,
                'product_id' => $product->id,
                'quantity' => $line['quantity'],
                'unit_price' => round((float) $product->sale_price, 4),
            ]);
        }
    }

    /**
     * @param  list<array{product_id?: mixed, quantity?: mixed}>  $lines
     * @return list<array{product_id: int, quantity: float}>
     */
    private function normalizeLines(int $tenantUserId, array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = round((float) ($line['quantity'] ?? 0), 4);

            if ($productId < 1 || $qty <= 0) {
                continue;
            }

            $exists = FleetProduct::query()
                ->where('user_id', $tenantUserId)
                ->where('id', $productId)
                ->where('is_active', true)
                ->exists();

            if (! $exists) {
                throw new InvalidArgumentException('أحد الأصناف غير صالح أو غير نشط.');
            }

            $normalized[] = [
                'product_id' => $productId,
                'quantity' => $qty,
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

    private function assertOwned(FleetCustodyIssue $issue, int $tenantUserId): void
    {
        if ((int) $issue->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('سند العهدة غير تابع لهذا الحساب.');
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}

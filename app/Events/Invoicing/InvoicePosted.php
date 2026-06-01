<?php

declare(strict_types=1);

namespace App\Events\Invoicing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * يُطلق بعد نجاح ترحيل الفاتورة (مخزون + قيود) — للموديولات المستقبلية (شحن، CRM، إشعارات).
 * لا يجب أن يغيّر المستمعون أرصدة المخزن أو القيود المالية للفاتورة.
 */
final class InvoicePosted
{
    use Dispatchable;
    use SerializesModels;

    public const TYPE_SALES = 'sales';

    public const TYPE_PURCHASE = 'purchase';

    public function __construct(
        public readonly Model $invoice,
        public readonly string $type,
    ) {}
}

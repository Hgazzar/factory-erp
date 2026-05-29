# المساهمة في Factory ERP

شكراً للمساهمة. هذا المستودع يغطي عمليات مالية ومخزنية حساسة — نتبع قواعد بسيطة لحماية الأرقام.

## قاعدة ذهبية (محاسبة / مخزون / POS)

**أي إصلاح لخلل مالي أو مخزني يجب أن يبدأ باختبار يعيد إنتاج المشكلة (أو يمنع عودته)، ثم يُطبَّق الإصلاح.**

- الخدمات الحرجة: `app/Services/Accounting*`, `app/Services/Inventory*`, `app/Services/Pos*`, `InventoryAccountingService`, `InvoicePaymentRecordingService`, وما يعادلها.
- دورات العمل: إتمام إنتاج (`markCompleted`)، تأكيد توريد (`markAsDelivered`)، بيع نقطة البيع.
- لا تُدمَج تغييرات تعدّل منطق الأرصدة أو المخزون بدون اختبار يرافقها.

## تشغيل الاختبارات محلياً

الاختبارات الأساسية تستخدم SQLite in-memory و migrations خفيفة تحت `tests/database/migrations/` (وليس migrations الإنتاج الكاملة).

```bash
composer install
./vendor/bin/phpunit tests/Unit tests/Feature/Production tests/Feature/Delivery
```

## CI

على كل push و pull request إلى `main` / `master` / `develop` يُشغَّل workflow **PHPUnit (ERP core)** (انظر `.github/workflows/phpunit.yml`). يجب أن يمرّ قبل الدمج.

## اختبارات الواجهة (Blade)

- استخدم `<x-info>` للتسميات والعناوين المهمة مع مفاتيح من `config/hints.php`.
- لا تستخدم `<select>` HTML عادي للقوائم — استخدم `<x-searchable-select>` أو `<x-custom-select>`.

## بيانات قديمة (مستودعات)

أوامر إنتاج/توريد أُنشئت قبل ربط المستودعات قد تفتقد `raw_materials_warehouse_id` / `finished_goods_warehouse_id` / `warehouse_id`. الإتمام يتطلب تحديد المستودعات على الأمر (أو إعادة إنشائه). راجع migration `2026_05_29_100000_add_warehouse_ids_to_production_and_delivery_orders.php`.

## ما لا نطلبه في كل PR

- لا نستهدف تغطية 80% على كامل المشروع.
- لا نضيف Feature tests لكل Controller دون سبب عملي.
- P2 (HR، ZatCA، استيراد…) يُختبر عند تفعيل الوحدة أو عند ظهور خلل.

## أمان

- لا ترفع `.env` أو مفاتيح API.
- لا تُدخل بيانات حقيقية للعملاء في الاختبارات.

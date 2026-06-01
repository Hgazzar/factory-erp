# تقرير فحص — خطة التحصين (Anti-Duplication)

تاريخ التنفيذ: 2026-06-02

## ملخص

تم توحيد مسارات المخزون والقيود المحاسبية بحيث لا تُنفَّذ إلا من نقاط ترحيل الفواتير (أو إذن الإضافة المخزني للمشتريات)، مع ربط إلزامي بأمر البيع/الشراء للفواتير المخزنية (مع استثناءات محددة).

---

## جدول المسارات

| المسار المُلغى / المعطّل | ما كان يفعله | المسار المعتمد الوحيد |
|---------------------------|--------------|------------------------|
| `SalesOrderWebController@store` — خصم مخزون + `InventoryTransaction` | خصم `item_warehouse` عند إنشاء الأمر | **لا خصم** — أمر بيع وثيقة تشغيلية فقط |
| `SalesOrderWebController@completeAccounting` | قيد إيراد/COGS من الأمر | **معطّل** — `SalesInvoicePostingService` (إيراد + COGS + خصم مخزون) |
| `DeliveryOrder::markAsDelivered` — مخزون + قيد COGS | خصم وتكلفة عند التسليم | **لوجستي فقط** (حالة + تاريخ) — المخزون من `SalesInvoicePostingService` |
| `PurchaseOrderWebController@completeReceipt` | قيد مدين مخزون / دائن موردين بدون حركة كمية | **معطّل** — `PurchaseInvoicePostingService` أو `StockReceiptService` |
| إذن استلام `ReceiveNote` (لم يُغيّر) | وثيقة بدون مخزون (كما كان) | فاتورة مشتريات أو Stock In |
| ترحيل مكرر لنفس الفاتورة | احتمال خصم/إضافة مرتين | فحص `inventory_posted_at` + `InvoiceOrderLinkGuard` |

---

## المخزون

| العملية | المسار الوحيد |
|---------|----------------|
| **خصم** | `SalesInventoryService::postInvoice` ← يُستدعى من `SalesInvoicePostingService::postExisting` فقط |
| **إضافة** | `PurchaseInventoryService::postInvoice` ← من `PurchaseInvoicePostingService` **أو** `StockReceiptService::createReceipt` |

---

## المحاسبة (قيود الفاتورة)

| العملية | المسار الوحيد |
|---------|----------------|
| **مبيعات — إيراد + ضريبة + ذمم/نقد** | `SalesAccountingService::postRevenueJournal` داخل ترحيل فاتورة المبيعات |
| **مبيعات — COGS** | `SalesAccountingService::postCogsJournal` داخل ترحيل فاتورة المبيعات |
| **مشتريات — مخزون + مورد** | `PurchaseAccountingService::postPurchaseInvoice` داخل ترحيل فاتورة المشتريات |
| **Stock In** | قيود داخل `StockReceiptService` (حسب نوع التسوية) |

---

## الربط الإلزامي (`InvoiceOrderLinkGuard`)

| الحالة | `sales_order_id` / `purchase_order_id` |
|--------|----------------------------------------|
| فاتورة ببنود مخزنية + مصدر `order` | **مطلوب** — وإلا: `لا يمكن ترحيل الفاتورة دون ربطها بطلب مسبق` |
| مصدر `direct` | غير مطلوب |
| بدون بنود مخزنية (خدمات فقط) | غير مطلوب |
| فاتورة عقد / طلب خدمة (مبيعات) | معفاة |
| **POS** (`pos_sales`) | مسار مستقل — لا يمر عبر `sales_invoices` posting |
| **Stock In** | لا يُلزم بـ `purchase_order_id` |

---

## الأحداث (Events)

| الحدث | التوقيت | الاستخدام المستقبلي |
|-------|---------|---------------------|
| `App\Events\Invoicing\InvoicePosted` | بعد `DB::commit` لترحيل الفاتورة | شحن، CRM، إشعارات — **بدون** تعديل مخزون أو قيود الفاتورة |

---

## حقول قاعدة البيانات

- `sales_invoices.sales_order_id`, `sales_invoices.posting_source`
- `purchase_invoices.purchase_order_id`, `purchase_invoices.posting_source`

---

## واجهة المستخدم

- أمر بيع: زر «فاتورة مبيعات من الأمر» — إزالة نموذج الإكمال المحاسبي
- أمر شراء: رابط «فاتورة مشتريات من الأمر» — إزالة «تسجيل استلام وترحيل محاسبي»

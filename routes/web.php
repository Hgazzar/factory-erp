<?php

use App\Http\Controllers\AccountingDashboardController;
use App\Http\Controllers\AccountWebController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ApAgingController;
use App\Http\Controllers\Api\ProductSearchController;
use App\Http\Controllers\ApiTokenWebController;
use App\Http\Controllers\ArAgingController;
use App\Http\Controllers\AttachmentWebController;
use App\Http\Controllers\AuditLogWebController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BomListWebController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\Clinic\ClinicApiController;
use App\Http\Controllers\Clinic\ClinicAppointmentWebController;
use App\Http\Controllers\Clinic\ClinicClinicalNoteWebController;
use App\Http\Controllers\Clinic\ClinicDashboardController;
use App\Http\Controllers\Clinic\ClinicDoctorScheduleWebController;
use App\Http\Controllers\Clinic\ClinicMedicalAttachmentWebController;
use App\Http\Controllers\Clinic\ClinicPatientWebController;
use App\Http\Controllers\Clinic\ClinicPdfWebController;
use App\Http\Controllers\Clinic\ClinicPrescriptionWebController;
use App\Http\Controllers\Clinic\ClinicServiceWebController;
use App\Http\Controllers\Clinic\ClinicSettingsWebController;
use App\Http\Controllers\Clinic\Portal\ClinicPortalApiController;
use App\Http\Controllers\Clinic\Portal\ClinicPortalWebController;
use App\Http\Controllers\CommissionRuleWebController;
use App\Http\Controllers\CommissionWebController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\ContractWebController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CrmAppointmentWebController;
use App\Http\Controllers\CrmCustomerWebController;
use App\Http\Controllers\CrmDashboardController;
use App\Http\Controllers\CrmLoyaltyWebController;
use App\Http\Controllers\CrmMembershipWebController;
use App\Http\Controllers\CrmOpportunityWebController;
use App\Http\Controllers\CrmSegmentWebController;
use App\Http\Controllers\CustomerWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\DeliveryOrderWebController;
use App\Http\Controllers\DepartmentWebController;
use App\Http\Controllers\EinvoiceSettingsController;
use App\Http\Controllers\EmployeeWebController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FixedAssetCategoryController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\Fleet\FleetAgentWebController;
use App\Http\Controllers\Fleet\FleetCollectionWebController;
use App\Http\Controllers\Fleet\FleetCustodyReturnWebController;
use App\Http\Controllers\Fleet\FleetCustodyWebController;
use App\Http\Controllers\Fleet\FleetCustomerWebController;
use App\Http\Controllers\Fleet\FleetDashboardController;
use App\Http\Controllers\Fleet\FleetProductWebController;
use App\Http\Controllers\Fleet\FleetRouteWebController;
use App\Http\Controllers\Fleet\FleetStoreOrderWebController;
use App\Http\Controllers\HRAttendanceImportController;
use App\Http\Controllers\HRAttendanceWebController;
use App\Http\Controllers\HRDashboardController;
use App\Http\Controllers\HRLeaveRequestController;
use App\Http\Controllers\HROvertimeWebController;
use App\Http\Controllers\InstallmentWebController;
use App\Http\Controllers\InventoryDashboardController;
use App\Http\Controllers\ItemBomController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemWebController;
use App\Http\Controllers\JournalEntryWebController;
use App\Http\Controllers\LedgerWebController;
use App\Http\Controllers\MachineWebController;
use App\Http\Controllers\ManufacturingWebController;
use App\Http\Controllers\NotificationWebController;
use App\Http\Controllers\Nursery\NurseryAttendanceWebController;
use App\Http\Controllers\Nursery\NurseryCalendarWebController;
use App\Http\Controllers\Nursery\NurseryChildDailyActivityWebController;
use App\Http\Controllers\Nursery\NurseryChildWebController;
use App\Http\Controllers\Nursery\NurseryClassroomWebController;
use App\Http\Controllers\Nursery\NurseryDashboardController;
use App\Http\Controllers\Nursery\NurseryGuardianWebController;
use App\Http\Controllers\Nursery\NurserySettingsWebController;
use App\Http\Controllers\Nursery\NurseryStaffWebController;
use App\Http\Controllers\Nursery\NurserySubscriptionWebController;
use App\Http\Controllers\Nursery\NurseryUnitWebController;
use App\Http\Controllers\Nursery\Portal\NurseryPortalAuthController;
use App\Http\Controllers\Nursery\Portal\NurseryPortalCalendarWebController;
use App\Http\Controllers\Nursery\Portal\NurseryPortalChildWebController;
use App\Http\Controllers\Nursery\Portal\NurseryPortalFinanceWebController;
use App\Http\Controllers\Nursery\Portal\NurseryPortalWebController;
use App\Http\Controllers\OperationsDashboardController;
use App\Http\Controllers\OperationsShiftController;
use App\Http\Controllers\PaymentMethodAccountController;
use App\Http\Controllers\PaymentWebController;
use App\Http\Controllers\PayrollItemController;
use App\Http\Controllers\PayrollWebController;
use App\Http\Controllers\Pos\PosProductWebController;
use App\Http\Controllers\Pos\PosTerminalApiController;
use App\Http\Controllers\Pos\PosTerminalWebController;
use App\Http\Controllers\PosDashboardController;
use App\Http\Controllers\PosDeviceWebController;
use App\Http\Controllers\PosSaleWebController;
use App\Http\Controllers\PosSessionWebController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProcurementDashboardController;
use App\Http\Controllers\ProductionEntryWebController;
use App\Http\Controllers\ProductionOrderWebController;
use App\Http\Controllers\ProductionReportWebController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfitLossReportWebController;
use App\Http\Controllers\PurchaseInvoiceWebController;
use App\Http\Controllers\PurchaseOrderWebController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\PurchaseReturnWebController;
use App\Http\Controllers\ReceiptWebController;
use App\Http\Controllers\ReceiveNoteWebController;
use App\Http\Controllers\SalesDashboardController;
use App\Http\Controllers\SalesInvoiceWebController;
use App\Http\Controllers\SalesOrderWebController;
use App\Http\Controllers\SalesPaymentWebController;
use App\Http\Controllers\SalesQuotationWebController;
use App\Http\Controllers\SalesReturnWebController;
use App\Http\Controllers\SalesTargetWebController;
use App\Http\Controllers\ServiceOrderWebController;
use App\Http\Controllers\ServicesDashboardController;
use App\Http\Controllers\ShiftWebController;
use App\Http\Controllers\StatementReportWebController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\Store\OrderManagementController;
use App\Http\Controllers\Store\Portal\StorePortalApiController;
use App\Http\Controllers\Store\Portal\StorePortalWebController;
use App\Http\Controllers\Store\StorePaymobWebhookController;
use App\Http\Controllers\Store\StorePosSalePdfWebController;
use App\Http\Controllers\StoreSettingsWebController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\SupplierPaymentWebController;
use App\Http\Controllers\SupplierWebController;
use App\Http\Controllers\SystemMaintenanceController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TaxReportWebController;
use App\Http\Controllers\TechnicianServiceOrderController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\WarehouseWebController;
use App\Services\ZatcaService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| System Configuration & Security
|--------------------------------------------------------------------------
*/
if (app()->environment('production')) {
    URL::forceScheme('https');
}

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

/*
|--------------------------------------------------------------------------
| TEMPORARY — ZATCA CSR على Railway (احذف هذا الراوت بعد توليد CSR)
|--------------------------------------------------------------------------
| 1) في Railway Variables: ZATCA_CSR_ROUTE_SECRET=قيمة_عشوائية_قوية
| 2) افتح: /__zatca-generate-csr?token=نفس_القيمة
| 3) احذف الكتلة أدناه وامسح المتغير من الإنتاج.
*/
Route::get('/__zatca-generate-csr', function () {
    $expected = (string) config('zatca.csr_route_secret');
    if ($expected === '') {
        abort(404);
    }
    $given = (string) request()->query('token', '');
    if (! hash_equals($expected, $given)) {
        abort(404);
    }

    try {
        // تحديث الرقم الضريبي يدوياً للتأكد من مطابقته لشروط الهيئة
        DB::table('einvoice_settings')->update([
            'zatca_tax_number' => '312345678901233',
        ]);

        /** @var ZatcaService $zatca */
        $zatca = app(ZatcaService::class);
        $setting = $zatca->generateAndStoreCsrForEinvoiceSettings();

        $csrPath = $setting->csr_path;
        if ($csrPath === null || $csrPath === '') {
            return response()->json([
                'ok' => false,
                'message' => 'لم يُسجَّل مسار CSR بعد التوليد.',
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }

        $content = Storage::disk('local')->get($csrPath);
        if ($content === false || $content === '') {
            return response()->json([
                'ok' => false,
                'message' => 'تعذّر قراءة ملف CSR من التخزين المحلي: '.$csrPath,
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'ok' => true,
            'message' => 'تم حفظ CSR والمفتاح الخاص في قاعدة البيانات والتخزين.',
            'csr_path' => $csrPath,
            'csr_pem' => $content,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'message' => $e->getMessage(),
        ], 500, [], JSON_UNESCAPED_UNICODE);
    }
})->name('zatca.temp.generate-csr');

/*
|--------------------------------------------------------------------------
| Clinic Patient Portal (Guest — no staff auth)
|--------------------------------------------------------------------------
*/
Route::prefix('s/{tenant_slug}')
    ->name('store.portal.')
    ->middleware(['store.portal.tenant', 'feature:online_store'])
    ->group(function () {
        Route::get('/', [StorePortalWebController::class, 'home'])->name('home');
        Route::get('shop', [StorePortalWebController::class, 'shop'])->name('shop');
        Route::get('offers', [StorePortalWebController::class, 'offers'])->name('offers');
        Route::get('p/{product}', [StorePortalWebController::class, 'product'])->whereNumber('product')->name('product');
        Route::get('cart', [StorePortalWebController::class, 'cart'])->name('cart');
        Route::get('checkout', [StorePortalWebController::class, 'checkout'])->name('checkout');
        Route::get('order/success/{saleId}', [StorePortalWebController::class, 'orderSuccess'])->whereNumber('saleId')->name('order.success');
        Route::get('about', [StorePortalWebController::class, 'about'])->name('about');
        Route::get('contact', [StorePortalWebController::class, 'contact'])->name('contact');
        Route::get('faq', [StorePortalWebController::class, 'faq'])->name('faq');
        Route::get('privacy', [StorePortalWebController::class, 'privacy'])->name('privacy');
        Route::get('shipping', [StorePortalWebController::class, 'shipping'])->name('shipping');
        Route::get('returns', [StorePortalWebController::class, 'returns'])->name('returns');
        Route::match(['get', 'post'], 'track-order', [StorePortalWebController::class, 'track'])->name('track');
        Route::get('order/{saleId}/invoice.pdf', [StorePosSalePdfWebController::class, 'portalReceipt'])
            ->whereNumber('saleId')
            ->middleware('signed')
            ->name('invoice.pdf');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('payment-methods', [StorePortalApiController::class, 'paymentMethods'])->name('payment-methods');
            Route::get('categories', [StorePortalApiController::class, 'categories'])->name('categories');
            Route::get('products', [StorePortalApiController::class, 'products'])->name('products');
            Route::get('products/{product}', [StorePortalApiController::class, 'showProduct'])->whereNumber('product')->name('products.show');
            Route::post('quote', [StorePortalApiController::class, 'quote'])->name('quote');
            Route::post('coupon', [StorePortalApiController::class, 'applyCoupon'])->name('coupon');
            Route::post('checkout', [StorePortalApiController::class, 'checkout'])->name('checkout');
        });
    });

/*
| Paymob webhooks — Store module only (Phase 4 checkout boundary).
| Clinic/Nursery billing must not register duplicate payment callbacks.
*/
Route::post('webhooks/store/paymob', StorePaymobWebhookController::class)
    ->name('store.webhooks.paymob');

Route::prefix('c/{tenant_slug}')
    ->name('clinic.portal.')
    ->middleware(['clinic.portal.tenant', 'feature:clinic_patient_portal'])
    ->group(function () {
        Route::get('book', [ClinicPortalWebController::class, 'book'])->name('book');
        Route::middleware('feature:clinic_appointment_self_management')->group(function () {
            Route::get('manage/{token}', [ClinicPortalWebController::class, 'manage'])->name('manage');
            Route::post('manage/{token}/cancel', [ClinicPortalWebController::class, 'cancel'])->name('manage.cancel');
            Route::post('manage/{token}/reschedule', [ClinicPortalWebController::class, 'reschedule'])->name('manage.reschedule');
        });

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('specialties', [ClinicPortalApiController::class, 'specialties'])->name('specialties');
            Route::get('doctors', [ClinicPortalApiController::class, 'doctors'])->name('doctors');
            Route::get('dates', [ClinicPortalApiController::class, 'dates'])->name('dates');
            Route::get('slots', [ClinicPortalApiController::class, 'slots'])->name('slots');
            Route::post('book', [ClinicPortalApiController::class, 'book'])->name('book');
            Route::middleware('feature:clinic_appointment_self_management')->group(function () {
                Route::post('manage/{token}/cancel', [ClinicPortalApiController::class, 'cancel'])->name('manage-api.cancel');
                Route::post('manage/{token}/reschedule', [ClinicPortalApiController::class, 'reschedule'])->name('manage-api.reschedule');
            });
        });
    });

Route::prefix('nursery-portal/{tenant_slug}')
    ->name('nursery.portal.')
    ->middleware(['nursery.portal.tenant', 'feature:nursery_portal'])
    ->group(function () {
        Route::get('/', [NurseryPortalWebController::class, 'login'])->name('login');
        Route::post('otp/request', [NurseryPortalAuthController::class, 'requestOtp'])->name('otp.request');
        Route::post('otp/verify', [NurseryPortalAuthController::class, 'verifyOtp'])->name('otp.verify');
        Route::get('invite/{token}', [NurseryPortalWebController::class, 'acceptInvite'])->name('invite');

        Route::middleware('nursery.portal.guardian')->group(function () {
            Route::get('home', [NurseryPortalWebController::class, 'home'])->name('home');
            Route::get('children/{childId}', [NurseryPortalChildWebController::class, 'show'])
                ->whereNumber('childId')
                ->name('children.show');
            Route::get('finance', [NurseryPortalFinanceWebController::class, 'index'])->name('finance');
            Route::get('calendar', [NurseryPortalCalendarWebController::class, 'index'])->name('calendar');
            Route::post('logout', [NurseryPortalWebController::class, 'logout'])->name('logout');
        });
    });

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'worker.scope'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Technician Access
    Route::middleware(['technician_or_admin', 'module:services'])->prefix('services/technician')->name('services.technician.')->group(function () {
        Route::get('/', [TechnicianServiceOrderController::class, 'index'])->name('index');
        Route::patch('orders/{order}', [TechnicianServiceOrderController::class, 'update'])->name('orders.update');
    });

    // Notifications & Profile
    Route::get('/notifications', [NotificationWebController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationWebController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // نقاط البيع — خارج تقييد role:admin حتى يفتح الكاشير/المشرف الواجهة (البيانات مقيّدة بالمستأجر في النماذج).
    Route::prefix('pos')->name('pos.')->middleware('module:pos')->group(function () {
        Route::get('dashboard', [PosDashboardController::class, 'index'])->name('dashboard');
        Route::get('cashier', [PosTerminalWebController::class, 'index'])->name('cashier');
        Route::get('terminal/receipt/{pos_sale}', [PosTerminalWebController::class, 'receipt'])->name('terminal.receipt');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('categories', [PosTerminalApiController::class, 'categories'])->name('categories');
            Route::get('products', [PosTerminalApiController::class, 'products'])->name('products');
            Route::get('products/lookup', [PosTerminalApiController::class, 'lookup'])->name('products.lookup');
            Route::post('checkout', [PosTerminalApiController::class, 'checkout'])->name('checkout');
        });

        Route::get('sessions', [PosSessionWebController::class, 'index'])->name('sessions.index');
        Route::middleware(['role:admin', 'feature:retail_pos_device_link'])->group(function () {
            Route::get('devices', [PosDeviceWebController::class, 'index'])->name('devices.index');
            Route::post('devices', [PosDeviceWebController::class, 'store'])->name('devices.store');
        });
        Route::post('sessions', [PosSessionWebController::class, 'store'])->name('sessions.store');
        Route::get('products', [PosProductWebController::class, 'index'])->name('products.index');
        Route::post('products', [PosProductWebController::class, 'store'])->name('products.store');
        Route::patch('products/{pos_product}/online', [PosProductWebController::class, 'updateOnlineVisibility'])->name('products.online');
        Route::patch('products/{pos_product}', [PosProductWebController::class, 'update'])->name('products.update');

        Route::get('receipts', [PosSaleWebController::class, 'index'])->name('receipts.index');
        Route::get('orders', [OrderManagementController::class, 'index'])->name('orders.index');
        Route::get('orders/{pos_sale}/invoice.pdf', [StorePosSalePdfWebController::class, 'merchantReceipt'])->name('orders.invoice.pdf');
        Route::get('orders/{pos_sale}/payment-receipt', [OrderManagementController::class, 'paymentReceipt'])->name('orders.payment-receipt');
        Route::post('orders/{pos_sale}/status', [OrderManagementController::class, 'updateStatus'])->name('orders.update-status');
        Route::redirect('online-orders', 'orders')->name('online-orders.index');
        Route::post('sales', [PosSaleWebController::class, 'store'])->name('sales.store');
        Route::get('sales/{pos_sale}', [PosSaleWebController::class, 'show'])->name('sales.show');
    });

    Route::middleware(['can:manage_payroll', 'module:hr'])->prefix('hr')->name('hr.')->group(function () {
        Route::redirect('salaries', '/hr/payrolls', 301)->name('salaries');
        Route::get('payrolls/payslips', [PayrollWebController::class, 'payslips'])->name('payrolls.payslips');
        Route::get('payrolls', [PayrollWebController::class, 'index'])->name('payrolls.index');
        Route::get('payrolls/create', [PayrollWebController::class, 'create'])->name('payrolls.create');
        Route::post('payrolls', [PayrollWebController::class, 'store'])->name('payrolls.store');
        Route::get('payrolls/{payroll}', [PayrollWebController::class, 'show'])->name('payrolls.show');
        Route::get('payrolls/{payroll}/slips/{slip}/payslip', [PayrollItemController::class, 'payslip'])->name('payroll-slips.payslip');
        Route::post('payrolls/{payroll}/approve', [PayrollWebController::class, 'approve'])->name('payrolls.approve');
        Route::post('payrolls/{payroll}/pay', [PayrollWebController::class, 'pay'])->name('payrolls.pay');

        Route::get('overtime/create', [HROvertimeWebController::class, 'create'])->name('overtime.create');
        Route::get('overtime', [HROvertimeWebController::class, 'index'])->name('overtime');
        Route::post('overtime', [HROvertimeWebController::class, 'store'])->name('overtime.store');
        Route::post('overtime/{overtimeRequest}/approve', [HROvertimeWebController::class, 'approve'])->name('overtime.approve');
        Route::post('overtime/{overtimeRequest}/reject', [HROvertimeWebController::class, 'reject'])->name('overtime.reject');
    });
});

/*
|--------------------------------------------------------------------------
| Nursery — outside role:admin so supervisor staff can login via linked_user_id.
| Workers remain blocked by worker.scope. Capabilities still enforced server-side.
|--------------------------------------------------------------------------
*/
Route::prefix('nursery')->name('nursery.')->middleware(['auth', 'worker.scope', 'module:nursery'])->group(function () {
    Route::get('dashboard', [NurseryDashboardController::class, 'index'])
        ->middleware('nursery.capability:view_daily')
        ->name('dashboard');
    Route::get('portal/qr-download', [NurseryDashboardController::class, 'downloadPortalQr'])
        ->middleware(['nursery.capability:view_daily', 'feature:nursery_portal'])
        ->name('portal.qr-download');

    Route::middleware('nursery.capability:view_daily')->group(function () {
        Route::get('attendance', [NurseryAttendanceWebController::class, 'index'])->name('attendance.index');
        Route::get('attendance/report', [NurseryAttendanceWebController::class, 'report'])->name('attendance.report');

        Route::get('children', [NurseryChildWebController::class, 'index'])->name('children.index');
    });

    Route::middleware('nursery.capability:manage_child_activity')->group(function () {
        Route::post('children/{child}/daily-activities', [NurseryChildDailyActivityWebController::class, 'store'])->name('children.daily-activities.store');
        Route::patch('children/{child}/daily-activities/{activity}', [NurseryChildDailyActivityWebController::class, 'update'])->name('children.daily-activities.update');
        Route::delete('children/{child}/daily-activities/{activity}', [NurseryChildDailyActivityWebController::class, 'destroy'])->name('children.daily-activities.destroy');
    });

    Route::middleware('nursery.capability:manage_child_attendance')->group(function () {
        Route::post('attendance/check-in', [NurseryAttendanceWebController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('attendance/check-out', [NurseryAttendanceWebController::class, 'checkOut'])->name('attendance.check-out');
        Route::post('attendance/bulk-check-in', [NurseryAttendanceWebController::class, 'bulkCheckIn'])->name('attendance.bulk-check-in');
        Route::post('attendance/bulk-check-out', [NurseryAttendanceWebController::class, 'bulkCheckOut'])->name('attendance.bulk-check-out');
        Route::patch('attendance/{log}/correct', [NurseryAttendanceWebController::class, 'correct'])->name('attendance.correct');
        Route::post('attendance/weekdays', [NurseryAttendanceWebController::class, 'storeWeekdays'])->name('attendance.weekdays');
        Route::post('attendance/leaves', [NurseryAttendanceWebController::class, 'storeLeave'])->name('attendance.leaves.store');
        Route::delete('attendance/leaves/{leave}', [NurseryAttendanceWebController::class, 'destroyLeave'])->name('attendance.leaves.destroy');
    });

    Route::middleware('nursery.capability:manage_staff_attendance')->group(function () {
        Route::post('attendance/staff/check-in', [NurseryAttendanceWebController::class, 'staffCheckIn'])->name('attendance.staff.check-in');
        Route::post('attendance/staff/check-out', [NurseryAttendanceWebController::class, 'staffCheckOut'])->name('attendance.staff.check-out');
        Route::post('attendance/staff/bulk-check-in', [NurseryAttendanceWebController::class, 'staffBulkCheckIn'])->name('attendance.staff.bulk-check-in');
        Route::post('attendance/staff/bulk-check-out', [NurseryAttendanceWebController::class, 'staffBulkCheckOut'])->name('attendance.staff.bulk-check-out');
    });

    Route::middleware('nursery.capability:manage_children')->group(function () {
        Route::get('partials/city-select', [NurseryChildWebController::class, 'citySelectPartial'])->name('partials.city-select');
        Route::get('children/create', [NurseryChildWebController::class, 'create'])->name('children.create');
        Route::post('children', [NurseryChildWebController::class, 'store'])->name('children.store');
        Route::get('children/{child}/edit', [NurseryChildWebController::class, 'edit'])->name('children.edit');
        Route::put('children/{child}', [NurseryChildWebController::class, 'update'])->name('children.update');
        Route::post('children/{child}/portal-invite', [NurseryChildWebController::class, 'sendPortalInvite'])->name('children.portal-invite');

        Route::post('guardians/{guardian}/portal-invite', [NurseryGuardianWebController::class, 'sendPortalInvite'])->name('guardians.portal-invite');
        Route::delete('guardians/{guardian}/revoke-portal', [NurseryGuardianWebController::class, 'revokePortalAccess'])->name('guardians.revoke-portal');
    });

    Route::middleware('nursery.capability:view_daily')->group(function () {
        Route::get('guardians', [NurseryGuardianWebController::class, 'index'])->name('guardians.index');
        Route::get('guardians/{guardian}', [NurseryGuardianWebController::class, 'show'])->name('guardians.show');
        Route::get('children/{child}', [NurseryChildWebController::class, 'show'])->name('children.show');
    });

    Route::middleware('nursery.capability:view_daily')->group(function () {
        Route::get('classrooms', [NurseryClassroomWebController::class, 'index'])->name('classrooms.index');
        Route::get('classrooms/today', [NurseryClassroomWebController::class, 'todayRedirect'])->name('classrooms.today.redirect');
        Route::get('classrooms/{classroom}/today', [NurseryClassroomWebController::class, 'today'])->name('classrooms.today');
    });

    Route::middleware('nursery.capability:manage_classrooms')->group(function () {
        Route::get('classrooms/create', [NurseryClassroomWebController::class, 'create'])->name('classrooms.create');
        Route::post('classrooms', [NurseryClassroomWebController::class, 'store'])->name('classrooms.store');
        Route::get('classrooms/{classroom}/edit', [NurseryClassroomWebController::class, 'edit'])->name('classrooms.edit');
        Route::put('classrooms/{classroom}', [NurseryClassroomWebController::class, 'update'])->name('classrooms.update');
    });

    Route::middleware('nursery.capability:view_staff')->group(function () {
        Route::get('staff', [NurseryStaffWebController::class, 'index'])->name('staff.index');
    });

    Route::middleware('nursery.capability:manage_staff')->group(function () {
        Route::get('staff/partials/city-select', [NurseryStaffWebController::class, 'citySelectPartial'])->name('staff.partials.city-select');
        Route::get('staff/create', [NurseryStaffWebController::class, 'create'])->name('staff.create');
        Route::post('staff', [NurseryStaffWebController::class, 'store'])->name('staff.store');
        Route::get('staff/{employee}/edit', [NurseryStaffWebController::class, 'edit'])->name('staff.edit');
        Route::put('staff/{employee}', [NurseryStaffWebController::class, 'update'])->name('staff.update');
    });

    Route::middleware('nursery.capability:view_units')->group(function () {
        Route::get('units', [NurseryUnitWebController::class, 'index'])->name('units.index');
    });

    Route::middleware('nursery.capability:manage_units')->group(function () {
        Route::get('units/create', [NurseryUnitWebController::class, 'create'])->name('units.create');
        Route::post('units', [NurseryUnitWebController::class, 'store'])->name('units.store');
        Route::get('units/{unit}/edit', [NurseryUnitWebController::class, 'edit'])->name('units.edit');
        Route::put('units/{unit}', [NurseryUnitWebController::class, 'update'])->name('units.update');
    });

    Route::middleware('nursery.capability:view_calendar')->group(function () {
        Route::get('calendar', [NurseryCalendarWebController::class, 'index'])->name('calendar.index');
        Route::get('calendar/events', [NurseryCalendarWebController::class, 'events'])->name('calendar.events');
    });

    Route::middleware('nursery.capability:manage_calendar')->group(function () {
        Route::get('calendar/create', [NurseryCalendarWebController::class, 'create'])->name('calendar.create');
        Route::post('calendar', [NurseryCalendarWebController::class, 'store'])->name('calendar.store');
        Route::get('calendar/lessons', [NurseryCalendarWebController::class, 'lessonOptions'])->name('calendar.lessons');
        Route::get('calendar/{entry}/edit', [NurseryCalendarWebController::class, 'edit'])->name('calendar.edit');
        Route::put('calendar/{entry}', [NurseryCalendarWebController::class, 'update'])->name('calendar.update');
        Route::delete('calendar/{entry}', [NurseryCalendarWebController::class, 'destroy'])->name('calendar.destroy');
    });

    Route::middleware('nursery.capability:view_subscriptions')->group(function () {
        Route::get('subscriptions', [NurserySubscriptionWebController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/plan-amount', [NurserySubscriptionWebController::class, 'planAmount'])->name('subscriptions.plan-amount');
    });

    Route::middleware('nursery.capability:manage_subscriptions')->group(function () {
        Route::post('subscriptions', [NurserySubscriptionWebController::class, 'store'])->name('subscriptions.store');
        Route::patch('subscriptions/{subscription}/cancel', [NurserySubscriptionWebController::class, 'cancel'])->name('subscriptions.cancel');
        Route::patch('subscriptions/{subscription}/mark-paid', [NurserySubscriptionWebController::class, 'markPaid'])->name('subscriptions.mark-paid');
        Route::post('subscriptions/{subscription}/renew', [NurserySubscriptionWebController::class, 'renew'])->name('subscriptions.renew');
        Route::post('subscriptions/reminders/payment', [NurserySubscriptionWebController::class, 'sendPaymentReminders'])->name('subscriptions.reminders.payment');
    });

    Route::middleware('nursery.capability:view_finance')->group(function () {
        Route::get('finance', [\App\Http\Controllers\Nursery\NurseryFinanceWebController::class, 'index'])->name('finance.index');
    });

    Route::middleware('nursery.capability:manage_settings')->group(function () {
        Route::get('settings', [NurserySettingsWebController::class, 'index'])->name('settings.index');
        Route::get('settings/partials/city-select', [NurserySettingsWebController::class, 'citySelectPartial'])->name('settings.partials.city-select');
        Route::put('settings/account', [NurserySettingsWebController::class, 'updateAccount'])->name('settings.account.update');
        Route::put('settings/branding', [NurserySettingsWebController::class, 'updateBranding'])->name('settings.branding.update');
        Route::post('settings/plans', [NurserySettingsWebController::class, 'storePlan'])->name('settings.plans.store');
        Route::put('settings/plans/{plan}', [NurserySettingsWebController::class, 'updatePlan'])->name('settings.plans.update');
        Route::delete('settings/plans/{plan}', [NurserySettingsWebController::class, 'destroyPlan'])->name('settings.plans.destroy');
        Route::post('settings/shifts', [NurserySettingsWebController::class, 'storeShifts'])->name('settings.shifts.store');
        Route::delete('settings/shifts/{shift}', [NurserySettingsWebController::class, 'destroyShift'])->name('settings.shifts.destroy');
        Route::put('settings/features', [NurserySettingsWebController::class, 'updateFeatures'])->name('settings.features.update');
    });
});

/*
|--------------------------------------------------------------------------
| Storage Access (Fallback for missing symlink)
|--------------------------------------------------------------------------
*/
Route::get('/storage/{path}', function (string $path) {
    $path = ltrim($path, '/');
    if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->where('path', '.*');

/*
|--------------------------------------------------------------------------
| Finance — خارج role:admin حتى يصل محاسب الحضانة (supervisor) حسب الصلاحيات.
| حماية: nursery.finance (admin لغير الحضانة / per-screen للحضانة).
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'worker.scope', 'module:finance', 'nursery.finance'])->group(function () {
    Route::prefix('finance')->name('finance.')->group(function () {

        Route::get('/', fn () => redirect()->route('finance.dashboard'))->name('index');
        Route::get('dashboard', [AccountingDashboardController::class, 'index'])->name('dashboard');
        Route::get('accounts', [AccountWebController::class, 'index'])->name('accounts.index');
        Route::get('accounts/import/template', [AccountWebController::class, 'importTemplate'])->name('accounts.import-template');
        Route::post('accounts/import', [AccountWebController::class, 'import'])->name('accounts.import');
        Route::get('accounts/create', [AccountWebController::class, 'create'])->name('accounts.create');
        Route::post('accounts', [AccountWebController::class, 'store'])->name('accounts.store');
        Route::put('accounts/{account}', [AccountWebController::class, 'update'])->name('accounts.update');
        Route::delete('accounts/{account}', [AccountWebController::class, 'destroy'])->name('accounts.destroy');
        Route::post('accounts/{account}/purge', [AccountWebController::class, 'purge'])->name('accounts.purge');
        Route::patch('accounts/{account}/toggle-active', [AccountWebController::class, 'toggleActive'])->name('accounts.toggle-active');
        // القيود والدفاتر
        Route::resource('journals', JournalEntryWebController::class);
        Route::get('ledger', [LedgerWebController::class, 'index'])->name('ledger.index');

        // سندات القبض والصرف
        Route::resource('receipts', ReceiptWebController::class)->only(['index', 'create', 'store']);
        Route::get('payments/supplier-purchase-invoices', [PaymentWebController::class, 'supplierPurchaseInvoices'])->name('payments.supplier-purchase-invoices');
        Route::resource('payments', PaymentWebController::class)->only(['index', 'create', 'store']);

        // الشيكات
        Route::prefix('cheques')->name('cheques.')->group(function () {
            Route::get('/', [ChequeController::class, 'index'])->name('index');
            Route::get('create/incoming', [ChequeController::class, 'createIncoming'])->name('create-incoming');
            Route::get('create/outgoing', [ChequeController::class, 'createOutgoing'])->name('create-outgoing');
            Route::post('/', [ChequeController::class, 'store'])->name('store');
            Route::resource('/', ChequeController::class)->except(['index', 'store', 'create']);
        });

        // موديول المصروفات (تم التحديث لدعم الطباعة والرفع)
        Route::get('expenses/{expense}/print', [ExpenseController::class, 'print'])->name('expenses.print');
        Route::get('expenses/{expense}/pdf', [ExpenseController::class, 'pdf'])->name('expenses.pdf');
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/back-to-draft', [ExpenseController::class, 'backToDraft'])->name('expenses.back-to-draft');
        Route::get('expenses/import/template', [ExpenseController::class, 'importTemplate'])->name('expenses.import-template');
        Route::post('expenses/import', [ExpenseController::class, 'import'])->name('expenses.import');
        Route::post('expenses/destroy-all-matching', [ExpenseController::class, 'destroyAllMatchingFilters'])->name('expenses.destroy-all-matching');
        // يجب تسجيله قبل resource('expenses') وإلا يُفسَّر «categories» كمعرّف مصروف ويُستدعى show غير الموجود
        Route::resource('expenses/categories', ExpenseCategoryController::class)->names('expenses.categories');
        Route::resource('expenses', ExpenseController::class);

        // الأصول الثابتة، مراكز التكلفة، والحسابات البنكية
        Route::resource('fixed-assets/categories', FixedAssetCategoryController::class)->names('fixed-assets.categories');
        Route::resource('fixed-assets', FixedAssetController::class);
        Route::resource('cost-centers', CostCenterController::class);
        Route::resource('bank-accounts', BankAccountController::class);
        Route::resource('tax-rates', TaxRateController::class)->except(['show']);
        Route::get('payment-method-accounts/edit', [PaymentMethodAccountController::class, 'edit'])->name('payment-method-accounts.edit');
        Route::put('payment-method-accounts', [PaymentMethodAccountController::class, 'update'])->name('payment-method-accounts.update');

        // إشعارات الخصم والإضافة
        Route::post('credit-notes/{creditNote}/approve', [CreditNoteController::class, 'approve'])->name('credit-notes.approve');
        Route::post('credit-notes/{creditNote}/cancel', [CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');
        Route::resource('credit-notes', CreditNoteController::class);

        Route::post('debit-notes/{debitNote}/approve', [DebitNoteController::class, 'approve'])->name('debit-notes.approve');
        Route::post('debit-notes/{debitNote}/cancel', [DebitNoteController::class, 'cancel'])->name('debit-notes.cancel');
        Route::resource('debit-notes', DebitNoteController::class);

        // الموازنات والتقارير المالية
        Route::post('budgets/{budget}/activate', [BudgetController::class, 'activate'])->name('budgets.activate');
        Route::post('budgets/{budget}/close', [BudgetController::class, 'close'])->name('budgets.close');
        Route::post('budgets/{budget}/archive', [BudgetController::class, 'archive'])->name('budgets.archive');
        Route::get('budgets/{budget}/export', [BudgetController::class, 'export'])->name('budgets.export');
        Route::resource('budgets', BudgetController::class);
        Route::get('bank-reconciliations', [BankReconciliationController::class, 'index'])->name('bank-reconciliations.index');
        Route::get('bank-reconciliations/create', [BankReconciliationController::class, 'create'])->name('bank-reconciliations.create');
        Route::post('bank-reconciliations', [BankReconciliationController::class, 'store'])->name('bank-reconciliations.store');
        Route::get('reports/trial-balance', [TrialBalanceController::class, 'index'])->name('reports.trial-balance');
        Route::get('reports/ar-aging', [ArAgingController::class, 'index'])->name('reports.ar-aging');
        Route::get('reports/ap-aging', [ApAgingController::class, 'index'])->name('reports.ap-aging');
        Route::get('reports/profit-loss', [ProfitLossReportWebController::class, 'index'])->name('reports.profit-loss');
    });

    Route::get('reports/tax', [TaxReportWebController::class, 'index'])->name('reports.tax.index');
});

/*
|--------------------------------------------------------------------------
| Admin Panel (Inventory, Production, Finance)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::delete('attachments/{attachment}', [AttachmentWebController::class, 'destroy'])->name('attachments.destroy');

    // إدارة العملاء (CRM) — role:admin يشمل admin و super_admin
    Route::prefix('crm')->name('crm.')->middleware('module:crm')->group(function () {
        Route::get('dashboard', [CrmDashboardController::class, 'index'])->name('dashboard');
        Route::get('customers', [CrmCustomerWebController::class, 'index'])->name('customers.index');
        Route::get('customers/new', [CrmCustomerWebController::class, 'createNewCustomer'])->name('customers.new');
        Route::post('customers/new', [CrmCustomerWebController::class, 'storeNewCustomer'])->name('customers.store-new');
        Route::get('customers/create', [CrmCustomerWebController::class, 'createLead'])->name('customers.create');
        Route::post('customers/lead', [CrmCustomerWebController::class, 'storeLead'])->name('customers.lead.store');
        Route::get('customers/{customer}', [CrmCustomerWebController::class, 'show'])->name('customers.show');
        Route::post('customers', [CrmCustomerWebController::class, 'store'])->name('customers.store');
        Route::post('customers/{customer}/actions/appointment', [CrmCustomerWebController::class, 'storeQuickAppointment'])->name('customers.actions.appointment');
        Route::post('customers/{customer}/actions/call', [CrmCustomerWebController::class, 'storeCallLog'])->name('customers.actions.call');
        Route::get('appointments', [CrmAppointmentWebController::class, 'index'])->name('appointments.index');
        Route::get('appointments/create', [CrmAppointmentWebController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [CrmAppointmentWebController::class, 'store'])->name('appointments.store');
        Route::get('activities/create', [CrmAppointmentWebController::class, 'createActivity'])->name('activities.create');
        Route::post('activities', [CrmAppointmentWebController::class, 'storeActivity'])->name('activities.store');
        Route::get('opportunities/pipeline', [CrmOpportunityWebController::class, 'pipeline'])->name('opportunities.pipeline');
        Route::get('opportunities/create', [CrmOpportunityWebController::class, 'create'])->name('opportunities.create');
        Route::post('opportunities', [CrmOpportunityWebController::class, 'store'])->name('opportunities.store');
        Route::get('opportunities/{opportunity}', [CrmOpportunityWebController::class, 'show'])->whereNumber('opportunity')->name('opportunities.show');
        Route::get('opportunities/{opportunity}/edit', [CrmOpportunityWebController::class, 'edit'])->whereNumber('opportunity')->name('opportunities.edit');
        Route::put('opportunities/{opportunity}', [CrmOpportunityWebController::class, 'update'])->whereNumber('opportunity')->name('opportunities.update');
        Route::get('opportunities', [CrmOpportunityWebController::class, 'index'])->name('opportunities.index');
        Route::get('segments', [CrmSegmentWebController::class, 'index'])->name('segments.index');
        Route::get('segments/create', [CrmSegmentWebController::class, 'create'])->name('segments.create');
        Route::post('segments', [CrmSegmentWebController::class, 'store'])->name('segments.store');
        Route::post('segments/{segment}/refresh-members', [CrmSegmentWebController::class, 'refreshMembers'])->name('segments.refresh-members');
        Route::get('loyalty', [CrmLoyaltyWebController::class, 'index'])->name('loyalty.index');
        Route::get('loyalty/create', [CrmLoyaltyWebController::class, 'create'])->name('loyalty.create');
        Route::post('loyalty', [CrmLoyaltyWebController::class, 'store'])->name('loyalty.store');
        Route::get('loyalty/accounts', [CrmLoyaltyWebController::class, 'accounts'])->name('loyalty.accounts.index');
        Route::get('memberships', [CrmMembershipWebController::class, 'index'])->name('memberships.index');
        Route::get('memberships/create', [CrmMembershipWebController::class, 'create'])->name('memberships.create');
        Route::post('memberships', [CrmMembershipWebController::class, 'store'])->name('memberships.store');
        Route::get('activities', [CrmAppointmentWebController::class, 'activities'])->name('activities.index');
        Route::view('settings', 'crm.settings-placeholder')->name('settings.index');
    });

    // العيادة (Clinic) — نيش medical_clinics
    Route::prefix('clinic')->name('clinic.')->middleware('module:clinic')->group(function () {
        Route::get('dashboard', [ClinicDashboardController::class, 'index'])
            ->middleware('clinic.capability:view_appointments')
            ->name('dashboard');
        Route::get('portal/qr-download', [ClinicDashboardController::class, 'downloadPortalQr'])
            ->middleware(['clinic.capability:view_appointments', 'feature:clinic_patient_portal'])
            ->name('portal.qr-download');

        Route::get('settings', [ClinicSettingsWebController::class, 'index'])
            ->middleware('clinic.capability:view_appointments')
            ->name('settings.index');
        Route::put('settings/branding', [ClinicSettingsWebController::class, 'updateBranding'])
            ->middleware('clinic.capability:view_appointments')
            ->name('settings.branding.update');

        Route::middleware('clinic.capability:view_appointments')->group(function () {
            Route::get('appointments', [ClinicAppointmentWebController::class, 'index'])->name('appointments.index');
            Route::post('appointments', [ClinicAppointmentWebController::class, 'store'])->name('appointments.store');
            Route::post('appointments/quick', [ClinicAppointmentWebController::class, 'quickStore'])->name('appointments.quick-store');
        });

        Route::patch('appointments/{appointment}/status', [ClinicAppointmentWebController::class, 'updateStatus'])
            ->middleware('clinic.capability:view_appointments')
            ->name('appointments.status');

        Route::post('api/quote-services', [ClinicApiController::class, 'quoteServices'])
            ->middleware('clinic.capability:collect_payment')
            ->name('api.quote-services');

        Route::post('api/upload-manual-prescription', [ClinicApiController::class, 'uploadManualPrescription'])
            ->middleware('clinic.capability:view_appointments')
            ->name('api.upload-manual-prescription');

        Route::middleware('clinic.capability:view_appointments')->group(function () {
            Route::get('patients', [ClinicPatientWebController::class, 'index'])->name('patients.index');
            Route::post('patients', [ClinicPatientWebController::class, 'store'])->name('patients.store');
            Route::get('patients/{patient}/edit', [ClinicPatientWebController::class, 'edit'])->name('patients.edit');
            Route::put('patients/{patient}', [ClinicPatientWebController::class, 'update'])->name('patients.update');
            Route::get('patients/{patient}', [ClinicPatientWebController::class, 'show'])->name('patients.show');
        });

        Route::middleware('clinic.capability:collect_payment')->group(function () {
            Route::get('appointments/{appointment}/receipt.pdf', [ClinicPdfWebController::class, 'receipt'])
                ->name('appointments.receipt.pdf');
        });

        Route::middleware('clinic.capability:view_clinical')->group(function () {
            Route::post('api/check-allergy', [ClinicApiController::class, 'checkAllergy'])->name('api.check-allergy');
            Route::patch('patients/{patient}/clinical', [ClinicPatientWebController::class, 'updateClinical'])->name('patients.clinical.update');
            Route::post('patients/{patient}/clinical-notes', [ClinicClinicalNoteWebController::class, 'store'])->name('clinical-notes.store');
            Route::patch('clinical-notes/{clinicalNote}', [ClinicClinicalNoteWebController::class, 'update'])->name('clinical-notes.update');
            Route::post('patients/{patient}/attachments', [ClinicMedicalAttachmentWebController::class, 'store'])->name('attachments.store');
            Route::get('attachments/{medicalAttachment}/preview', [ClinicMedicalAttachmentWebController::class, 'preview'])->name('attachments.preview');
            Route::get('attachments/{medicalAttachment}/download', [ClinicMedicalAttachmentWebController::class, 'download'])->name('attachments.download');
            Route::delete('attachments/{medicalAttachment}', [ClinicMedicalAttachmentWebController::class, 'destroy'])->name('attachments.destroy');

            Route::get('prescriptions', [ClinicPrescriptionWebController::class, 'index'])->name('prescriptions.index');
            Route::get('prescriptions/create', [ClinicPrescriptionWebController::class, 'create'])->name('prescriptions.create');
            Route::post('prescriptions', [ClinicPrescriptionWebController::class, 'store'])->name('prescriptions.store');
            Route::get('prescriptions/{prescription}', [ClinicPrescriptionWebController::class, 'show'])->name('prescriptions.show');
            Route::get('prescriptions/{prescription}/pdf', [ClinicPdfWebController::class, 'prescription'])
                ->name('prescriptions.pdf');
        });

        Route::middleware('clinic.capability:manage_services')->group(function () {
            Route::get('services', [ClinicServiceWebController::class, 'index'])->name('services.index');
            Route::post('services', [ClinicServiceWebController::class, 'store'])->name('services.store');

            Route::get('doctor-schedules', [ClinicDoctorScheduleWebController::class, 'index'])->name('doctor-schedules.index');
            Route::post('doctor-schedules', [ClinicDoctorScheduleWebController::class, 'storeSchedule'])->name('doctor-schedules.store');
            Route::delete('doctor-schedules/{schedule}', [ClinicDoctorScheduleWebController::class, 'destroySchedule'])->name('doctor-schedules.destroy');
            Route::post('blocked-slots', [ClinicDoctorScheduleWebController::class, 'storeBlocked'])->name('blocked-slots.store');
            Route::delete('blocked-slots/{blocked}', [ClinicDoctorScheduleWebController::class, 'destroyBlocked'])->name('blocked-slots.destroy');
        });
    });

    Route::prefix('fleet')->name('fleet.')->middleware(['module:fleet', 'fleet.access'])->group(function () {
        Route::get('dashboard', [FleetDashboardController::class, 'index'])
            ->middleware('fleet.capability:view_dashboard')
            ->name('dashboard');

        Route::middleware('fleet.capability:manage_agents')->group(function () {
            Route::get('agents', [FleetAgentWebController::class, 'index'])->name('agents.index');
            Route::get('agents/create', [FleetAgentWebController::class, 'create'])->name('agents.create');
            Route::post('agents', [FleetAgentWebController::class, 'store'])->name('agents.store');
            Route::get('agents/{agent}/edit', [FleetAgentWebController::class, 'edit'])->name('agents.edit');
            Route::put('agents/{agent}', [FleetAgentWebController::class, 'update'])->name('agents.update');
        });

        Route::middleware('fleet.capability:manage_customers')->group(function () {
            Route::get('customers', [FleetCustomerWebController::class, 'index'])->name('customers.index');
            Route::get('customers/create', [FleetCustomerWebController::class, 'create'])->name('customers.create');
            Route::post('customers', [FleetCustomerWebController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}/edit', [FleetCustomerWebController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [FleetCustomerWebController::class, 'update'])->name('customers.update');
            Route::post('customers/{customer}/approve-location', [FleetCustomerWebController::class, 'approveLocation'])->name('customers.approve-location');
        });

        Route::middleware('fleet.capability:manage_products')->group(function () {
            Route::get('products', [FleetProductWebController::class, 'index'])->name('products.index');
            Route::get('products/create', [FleetProductWebController::class, 'create'])->name('products.create');
            Route::post('products', [FleetProductWebController::class, 'store'])->name('products.store');
            Route::get('products/{product}/edit', [FleetProductWebController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [FleetProductWebController::class, 'update'])->name('products.update');
            Route::post('products/{product}/publish', [FleetProductWebController::class, 'publish'])->name('products.publish');
        });

        Route::middleware('fleet.capability:view_routes')->group(function () {
            Route::get('routes', [FleetRouteWebController::class, 'index'])->name('routes.index');
        });

        Route::middleware('fleet.capability:manage_routes')->group(function () {
            Route::get('routes/create', [FleetRouteWebController::class, 'create'])->name('routes.create');
            Route::post('routes', [FleetRouteWebController::class, 'store'])->name('routes.store');
            Route::get('routes/{route}/edit', [FleetRouteWebController::class, 'edit'])->name('routes.edit');
            Route::put('routes/{route}', [FleetRouteWebController::class, 'update'])->name('routes.update');
            Route::post('routes/{route}/start', [FleetRouteWebController::class, 'start'])->name('routes.start');
            Route::post('routes/{route}/complete', [FleetRouteWebController::class, 'complete'])->name('routes.complete');
            Route::post('routes/{route}/cancel', [FleetRouteWebController::class, 'cancel'])->name('routes.cancel');
            Route::patch('route-stops/{stop}/status', [FleetRouteWebController::class, 'updateStopStatus'])->name('route-stops.status');
        });

        Route::middleware('fleet.capability:view_routes')->group(function () {
            Route::get('routes/{route}', [FleetRouteWebController::class, 'show'])->name('routes.show');
        });

        Route::middleware('fleet.capability:view_custody')->group(function () {
            Route::get('custody', [FleetCustodyWebController::class, 'index'])->name('custody.index');
            Route::get('custody/balances', [FleetCustodyWebController::class, 'balances'])->name('custody.balances');
            Route::get('custody/balances/{agent}', [FleetCustodyWebController::class, 'agentBalance'])->name('custody.balances.agent');
            Route::get('custody/returns', [FleetCustodyReturnWebController::class, 'index'])->name('custody.returns.index');
        });

        Route::middleware('fleet.capability:manage_custody')->group(function () {
            Route::get('custody/create', [FleetCustodyWebController::class, 'create'])->name('custody.create');
            Route::post('custody', [FleetCustodyWebController::class, 'store'])->name('custody.store');
            Route::post('custody/{custody}/confirm', [FleetCustodyWebController::class, 'confirm'])->name('custody.confirm');
            Route::post('custody/{custody}/void', [FleetCustodyWebController::class, 'void'])->name('custody.void');
            Route::get('custody/returns/create', [FleetCustodyReturnWebController::class, 'create'])->name('custody.returns.create');
            Route::post('custody/returns', [FleetCustodyReturnWebController::class, 'store'])->name('custody.returns.store');
            Route::post('custody/returns/{custodyReturn}/confirm', [FleetCustodyReturnWebController::class, 'confirm'])->name('custody.returns.confirm');
            Route::post('custody/returns/{custodyReturn}/void', [FleetCustodyReturnWebController::class, 'void'])->name('custody.returns.void');
        });

        Route::middleware('fleet.capability:view_custody')->group(function () {
            Route::get('custody/returns/{custodyReturn}', [FleetCustodyReturnWebController::class, 'show'])->name('custody.returns.show');
            Route::get('custody/{custody}', [FleetCustodyWebController::class, 'show'])->name('custody.show');
        });

        Route::middleware('fleet.capability:view_collections')->group(function () {
            Route::get('collections', [FleetCollectionWebController::class, 'index'])->name('collections.index');
        });

        Route::middleware('fleet.capability:manage_collections')->group(function () {
            Route::get('collections/create', [FleetCollectionWebController::class, 'create'])->name('collections.create');
            Route::post('collections', [FleetCollectionWebController::class, 'store'])->name('collections.store');
            Route::post('collections/{collection}/confirm', [FleetCollectionWebController::class, 'confirm'])->name('collections.confirm');
            Route::post('collections/{collection}/void', [FleetCollectionWebController::class, 'void'])->name('collections.void');
        });

        Route::middleware('fleet.capability:view_collections')->group(function () {
            Route::get('collections/{collection}', [FleetCollectionWebController::class, 'show'])->name('collections.show');
        });

        Route::middleware('fleet.capability:view_store_orders')->group(function () {
            Route::get('store-orders', [FleetStoreOrderWebController::class, 'index'])->name('store-orders.index');
        });

        Route::middleware('fleet.capability:manage_store_orders')->group(function () {
            Route::post('store-orders/{sale}/assign-route', [FleetStoreOrderWebController::class, 'assignRoute'])
                ->whereNumber('sale')
                ->name('store-orders.assign-route');
            Route::post('store-orders/{sale}/assign-agent', [FleetStoreOrderWebController::class, 'assignAgent'])
                ->whereNumber('sale')
                ->name('store-orders.assign-agent');
        });
    });

    // Inventory & Items
    Route::middleware('module:inventory')->group(function () {
        Route::get('api/products/search', [ProductSearchController::class, 'search'])->name('api.products.search');
        Route::get('items/{item}', [ItemWebController::class, 'show'])->whereNumber('item')->name('items.show');
        Route::put('items/{item}/bom', [ItemBomController::class, 'update'])->whereNumber('item')->name('items.bom.update');
        Route::resource('items', ItemWebController::class)->except('show');
        Route::post('items/import', [ItemWebController::class, 'import'])->name('items.import');
        Route::get('items/import/template', [ItemWebController::class, 'importTemplate'])->name('items.import-template');
        Route::resource('warehouses', WarehouseWebController::class);

        // Inventory Management
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryDashboardController::class, 'index'])->name('dashboard');
            Route::resource('item-categories', ItemCategoryController::class)->except(['show']);
            Route::resource('transfers', \App\Http\Controllers\StockTransferController::class);
            Route::get('transfers/items-by-warehouse', [\App\Http\Controllers\StockTransferController::class, 'itemsByWarehouse'])->name('transfers.items-by-warehouse');
            Route::resource('adjustments', \App\Http\Controllers\StockAdjustmentController::class);
            Route::get('adjustments/items-for-adjustment', [\App\Http\Controllers\StockAdjustmentController::class, 'itemsForAdjustment'])->name('adjustments.items-for-adjustment');
            Route::resource('stock-in', StockInController::class);
            Route::get('audits/items-for-audit', [\App\Http\Controllers\InventoryAuditController::class, 'itemsForAudit'])->name('audits.items-for-audit');
            Route::resource('audits', \App\Http\Controllers\InventoryAuditController::class);
            Route::post('audits/{audit}/approve', [\App\Http\Controllers\InventoryAuditController::class, 'approve'])->name('audits.approve');
            Route::get('movements', [\App\Http\Controllers\StockMovementController::class, 'index'])->name('movements.index');
            Route::get('reports/valuation', [\App\Http\Controllers\InventoryValuationReportWebController::class, 'index'])->name('reports.valuation');
            Route::resource('price-lists', \App\Http\Controllers\PriceListController::class);
        });
    }); // module:inventory

    // Legacy production + manufacturing
    Route::middleware('module:manufacturing')->group(function () {
        Route::redirect('production-lines', 'manufacturing', 301)->name('production-lines.index');
        Route::redirect('production-lines/create', 'manufacturing', 301)->name('production-lines.create');
        Route::get('production-lines/{any}', fn () => redirect()->route('manufacturing.dashboard'))
            ->where('any', '.*')
            ->name('production-lines.legacy');
        Route::resource('machines', MachineWebController::class);
        Route::prefix('production-orders')->name('production-orders.')->group(function () {
            Route::redirect('/', '/manufacturing/runs', 301)->name('index');
            Route::redirect('create', '/manufacturing/create', 301)->name('create');
            Route::get('items/{item}/bom-suggestions', fn () => redirect()->route('manufacturing.runs.index', [], 301))->name('bom-suggestions');
            Route::get('{production_order}/ingredient-shortage', fn () => redirect()->route('manufacturing.runs.index', [], 301))->name('ingredient-shortage');
            Route::match(['post'], '{production_order}/prefill-purchase', fn () => redirect()->route('manufacturing.runs.index'))->name('prefill-purchase');
            Route::post('{production_order}/complete', [ProductionOrderWebController::class, 'complete'])->name('complete');
            Route::match(['post'], '/', fn () => redirect()->route('manufacturing.runs.index'))->name('store');
            Route::get('{production_order}', [ProductionOrderWebController::class, 'show'])->name('show');
        });

        Route::prefix('manufacturing')->name('manufacturing.')->group(function () {
            Route::get('/', [ManufacturingWebController::class, 'dashboard'])->name('dashboard');
            Route::get('runs', [ManufacturingWebController::class, 'index'])->name('runs.index');
            Route::prefix('bom-lists')->name('bom-lists.')->group(function () {
                Route::get('/', [BomListWebController::class, 'index'])->name('index');
                Route::get('create', [BomListWebController::class, 'create'])->name('create');
                Route::post('/', [BomListWebController::class, 'store'])->name('store');
                Route::get('{bom_list}', [BomListWebController::class, 'show'])->name('show');
            });
            Route::get('create', [ManufacturingWebController::class, 'create'])->name('create');
            Route::get('reports/production-variance', [ManufacturingWebController::class, 'productionVarianceReport'])->name('reports.production-variance');
            Route::post('/', [ManufacturingWebController::class, 'store'])->name('store');
            Route::post('{manufacturing_run}/post', [ManufacturingWebController::class, 'post'])->name('post');
            Route::delete('{manufacturing_run}', [ManufacturingWebController::class, 'destroy'])->name('destroy');
            Route::get('{manufacturing_run}', [ManufacturingWebController::class, 'show'])->name('show');
        });
    }); // module:manufacturing (legacy + runs)

    // الموارد البشرية (HR)
    Route::prefix('hr')->name('hr.')->middleware('module:hr')->group(function () {
        Route::redirect('/', '/hr/dashboard');
        Route::get('dashboard', [HRDashboardController::class, 'index'])->name('dashboard');
        Route::get('attendance', [HRAttendanceWebController::class, 'index'])->name('attendance');
        Route::get('attendance/import', fn () => redirect()->route('hr.attendance', ['open_import' => 1]))->name('attendance.import');
        Route::get('attendance/import/template', [HRAttendanceImportController::class, 'downloadTemplate'])->name('attendance.import.template');
        Route::post('attendance/import/preview', [HRAttendanceImportController::class, 'preview'])->name('attendance.import.preview');
        Route::post('attendance/import/execute', [HRAttendanceImportController::class, 'execute'])->name('attendance.import.execute');
        Route::get('leave-requests/create', [HRLeaveRequestController::class, 'create'])->name('leave-requests.create');
        Route::get('leave-requests', [HRLeaveRequestController::class, 'index'])->name('leave-requests');
        Route::post('leave-requests', [HRLeaveRequestController::class, 'store'])->name('leave-requests.store');
        Route::post('leave-requests/{leave}/approve', [HRLeaveRequestController::class, 'approve'])->name('leave-requests.approve');
        Route::post('leave-requests/{leave}/reject', [HRLeaveRequestController::class, 'reject'])->name('leave-requests.reject');
        Route::resource('shifts', ShiftWebController::class);
        Route::resource('departments', DepartmentWebController::class)->except(['show']);
        Route::get('employees/import', [EmployeeWebController::class, 'import'])->name('employees.import');
        Route::get('employees/export', [EmployeeWebController::class, 'export'])->name('employees.export');
        Route::resource('employees', EmployeeWebController::class);
    });

    // المشتريات (Purchases)
    Route::prefix('purchases')->name('purchases.')->middleware('module:purchases')->group(function () {
        Route::get('/', [ProcurementDashboardController::class, 'index'])->name('dashboard');
        Route::get('suppliers/{supplier}/documents/{document}/download', [SupplierWebController::class, 'downloadDocument'])->name('suppliers.documents.download');
        Route::get('suppliers/import/template', [SupplierWebController::class, 'importTemplate'])->name('suppliers.import-template');
        Route::post('suppliers/import', [SupplierWebController::class, 'import'])->name('suppliers.import');
        Route::resource('suppliers', SupplierWebController::class);
        Route::get('orders', [PurchaseOrderWebController::class, 'index'])->name('orders.index');
        Route::get('orders/import/template', [PurchaseOrderWebController::class, 'importTemplate'])->name('orders.import-template');
        Route::post('orders/import', [PurchaseOrderWebController::class, 'import'])->name('orders.import');
        Route::get('orders/create', [PurchaseOrderWebController::class, 'create'])->name('orders.create');
        Route::post('orders', [PurchaseOrderWebController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}/edit', [PurchaseOrderWebController::class, 'edit'])->name('orders.edit');
        Route::put('orders/{order}', [PurchaseOrderWebController::class, 'update'])->name('orders.update');
        Route::post('orders/{order}/complete-receipt', [PurchaseOrderWebController::class, 'completeReceipt'])->name('orders.complete-receipt');
        Route::delete('orders/{order}', [PurchaseOrderWebController::class, 'destroy'])->name('orders.destroy');
        Route::get('orders/{order}', [PurchaseOrderWebController::class, 'show'])->name('orders.show');
        Route::get('receive-notes', [ReceiveNoteWebController::class, 'index'])->name('receive-notes.index');
        Route::get('receive-notes/create', [ReceiveNoteWebController::class, 'create'])->name('receive-notes.create');
        Route::post('receive-notes', [ReceiveNoteWebController::class, 'store'])->name('receive-notes.store');
        Route::get('receive-notes/import/template', [ReceiveNoteWebController::class, 'importTemplate'])->name('receive-notes.import-template');
        Route::post('receive-notes/import', [ReceiveNoteWebController::class, 'import'])->name('receive-notes.import');
        Route::get('invoices/import/template', [PurchaseInvoiceWebController::class, 'importTemplate'])->name('invoices.import-template');
        Route::post('invoices/import', [PurchaseInvoiceWebController::class, 'import'])->name('invoices.import');
        Route::post('invoices/{invoice}/record-payment', [PurchaseInvoiceWebController::class, 'recordPayment'])->name('invoices.record-payment');
        Route::resource('invoices', PurchaseInvoiceWebController::class)->only(['index', 'create', 'store']);
        Route::get('payments/supplier-outstanding', [SupplierPaymentWebController::class, 'supplierOutstanding'])->name('payments.supplier-outstanding');
        Route::resource('payments', SupplierPaymentWebController::class)->only(['index', 'create', 'store']);
        Route::get('returns/invoices-by-supplier', [PurchaseReturnWebController::class, 'invoicesBySupplier'])->name('returns.invoices-by-supplier');
        Route::get('returns/invoice-items/{invoice}', [PurchaseReturnWebController::class, 'invoiceItems'])->name('returns.invoice-items');
        Route::resource('returns', PurchaseReturnWebController::class)->only(['index', 'create', 'store'])->names('returns');
        Route::get('reports', [PurchaseReportController::class, 'index'])->name('reports.index');
    });

    // المبيعات (Sales)
    Route::prefix('sales')->name('sales.')->middleware('module:sales')->group(function () {
        Route::get('/', [SalesDashboardController::class, 'index'])->name('dashboard');
        Route::get('customers/import/template', [CustomerWebController::class, 'importTemplate'])->name('customers.import-template');
        Route::post('customers/import', [CustomerWebController::class, 'import'])->name('customers.import');
        Route::post('customers/{customer}/loyalty-enroll', [CustomerWebController::class, 'enrollLoyaltyProgram'])->name('customers.loyalty-enroll');
        Route::resource('customers', CustomerWebController::class);
        Route::get('invoices/import/template', [SalesInvoiceWebController::class, 'importTemplate'])->name('invoices.import-template');
        Route::post('invoices/import', [SalesInvoiceWebController::class, 'import'])->name('invoices.import');
        Route::get('orders', [SalesOrderWebController::class, 'index'])->name('orders.index');
        Route::get('orders/import/template', [SalesOrderWebController::class, 'importTemplate'])->name('orders.import-template');
        Route::post('orders/import', [SalesOrderWebController::class, 'import'])->name('orders.import');
        Route::get('orders/create', [SalesOrderWebController::class, 'create'])->name('orders.create');
        Route::post('orders', [SalesOrderWebController::class, 'store'])->name('orders.store');
        Route::post('orders/{sales_order}/attachments', [SalesOrderWebController::class, 'storeAttachments'])->name('orders.attachments.store');
        Route::post('orders/{sales_order}/complete-accounting', [SalesOrderWebController::class, 'completeAccounting'])->name('orders.complete-accounting');
        Route::get('orders/{sales_order}', [SalesOrderWebController::class, 'show'])->name('orders.show');
        Route::get('orders/{sales_order}/print', [SalesOrderWebController::class, 'print'])->name('orders.print');
        Route::get('orders/{sales_order}/delivery-orders/create', [DeliveryOrderWebController::class, 'create'])->name('orders.delivery-orders.create');
        Route::post('orders/{sales_order}/delivery-orders', [DeliveryOrderWebController::class, 'store'])->name('orders.delivery-orders.store');
        Route::get('delivery-orders', [DeliveryOrderWebController::class, 'index'])->name('delivery-orders.index');
        Route::get('delivery-orders/{delivery_order}', [DeliveryOrderWebController::class, 'show'])->name('delivery-orders.show');
        Route::post('delivery-orders/{delivery_order}/deliver', [DeliveryOrderWebController::class, 'deliver'])->name('delivery-orders.deliver');
        Route::get('quotations', [SalesQuotationWebController::class, 'index'])->name('quotations.index');
        Route::get('quotations/import/template', [SalesQuotationWebController::class, 'importTemplate'])->name('quotations.import-template');
        Route::post('quotations/import', [SalesQuotationWebController::class, 'import'])->name('quotations.import');
        Route::get('quotations/create', [SalesQuotationWebController::class, 'create'])->name('quotations.create');
        Route::post('quotations', [SalesQuotationWebController::class, 'store'])->name('quotations.store');
        Route::get('quotations/{quotation}/edit', [SalesQuotationWebController::class, 'edit'])->name('quotations.edit');
        Route::put('quotations/{quotation}', [SalesQuotationWebController::class, 'update'])->name('quotations.update');
        Route::delete('quotations/{quotation}', [SalesQuotationWebController::class, 'destroy'])->name('quotations.destroy');
        Route::post('quotations/{quotation}/approve', [SalesQuotationWebController::class, 'approve'])->name('quotations.approve');
        Route::get('quotations/{quotation}/convert-to-order', [SalesQuotationWebController::class, 'convertToOrder'])->name('quotations.convert-to-order');
        Route::get('quotations/{quotation}/for-order', [SalesQuotationWebController::class, 'forOrder'])->name('quotations.for-order');
        Route::get('quotations/{quotation}/print', [SalesQuotationWebController::class, 'print'])->name('quotations.print');
        Route::get('quotations/{quotation}/pdf', [SalesQuotationWebController::class, 'pdf'])->name('quotations.pdf');
        Route::get('invoices/{invoice}/print', [SalesInvoiceWebController::class, 'print'])->name('invoices.print');
        Route::post('invoices/{invoice}/record-payment', [SalesInvoiceWebController::class, 'recordPayment'])->name('invoices.record-payment');
        Route::resource('invoices', SalesInvoiceWebController::class)->only(['index', 'create', 'store']);
        Route::get('payments/customer-outstanding', [SalesPaymentWebController::class, 'customerOutstanding'])->name('payments.customer-outstanding');
        Route::resource('payments', SalesPaymentWebController::class)->only(['index', 'create', 'store']);
        Route::get('returns/invoices-by-customer', [SalesReturnWebController::class, 'invoicesByCustomer'])->name('returns.invoices-by-customer');
        Route::get('returns/invoice-items/{invoice}', [SalesReturnWebController::class, 'invoiceItems'])->name('returns.invoice-items');
        Route::resource('returns', SalesReturnWebController::class)->only(['index', 'create', 'store'])->names('returns');
        Route::get('installments/invoices-for-customer', [InstallmentWebController::class, 'invoicesForCustomer'])->name('installments.invoices-for-customer');
        Route::post('installments/{installment}/send-reminder', [InstallmentWebController::class, 'sendReminder'])->name('installments.send-reminder');
        Route::resource('installments', InstallmentWebController::class)->only(['index', 'create', 'store'])->names('installments');
        Route::resource('targets', SalesTargetWebController::class)->only(['index', 'create', 'store'])->names('targets');
        Route::resource('contracts', ContractWebController::class)->only(['index', 'create', 'store'])->names('contracts');
        Route::get('einvoice/settings', [EinvoiceSettingsController::class, 'edit'])->name('einvoice.settings.edit');
        Route::put('einvoice/settings', [EinvoiceSettingsController::class, 'update'])->name('einvoice.settings.update');
        Route::post('einvoice/settings/onboarding', [EinvoiceSettingsController::class, 'completeOnboarding'])->name('einvoice.settings.onboarding');
        Route::post('commissions/calculate', [CommissionWebController::class, 'calculate'])->name('commissions.calculate');
        Route::get('commissions/rules', [CommissionRuleWebController::class, 'index'])->name('commissions.rules.index');
        Route::post('commissions/rules', [CommissionRuleWebController::class, 'store'])->name('commissions.rules.store');
        Route::resource('commissions', CommissionWebController::class)->only(['index'])->names('commissions');
    });

    // الخدمات والصيانة (لوحة + طلبات الخدمة)
    Route::prefix('services')->name('services.')->middleware('module:services')->group(function () {
        Route::get('dashboard', [ServicesDashboardController::class, 'index'])->name('dashboard');
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [ServiceOrderWebController::class, 'index'])->name('index');
            Route::get('create', [ServiceOrderWebController::class, 'create'])->name('create');
            Route::post('/', [ServiceOrderWebController::class, 'store'])->name('store');
            Route::get('{order}', [ServiceOrderWebController::class, 'show'])->name('show');
            Route::post('{order}/assign', [ServiceOrderWebController::class, 'assign'])->name('assign');
            Route::post('{order}/parts', [ServiceOrderWebController::class, 'addPart'])->name('parts.store');
            Route::post('{order}/complete', [ServiceOrderWebController::class, 'complete'])->name('complete');
            Route::post('{order}/cancel', [ServiceOrderWebController::class, 'cancel'])->name('cancel');
        });
    });

    // إعدادات المنشأة والسجلات
    Route::get('settings/company', [CompanySettingsController::class, 'edit'])->name('settings.company.edit');
    Route::put('settings/company', [CompanySettingsController::class, 'update'])->name('settings.company.update');
    Route::middleware('module:pos')->group(function () {
        Route::get('settings/store', [StoreSettingsWebController::class, 'edit'])->name('settings.store.edit');
        Route::put('settings/store', [StoreSettingsWebController::class, 'update'])->name('settings.store.update');
    });
    Route::get('settings/api-tokens', [ApiTokenWebController::class, 'index'])->name('settings.api-tokens.index');
    Route::post('settings/api-tokens', [ApiTokenWebController::class, 'store'])->name('settings.api-tokens.store');
    Route::delete('settings/api-tokens/{tokenId}', [ApiTokenWebController::class, 'destroy'])->whereNumber('tokenId')->name('settings.api-tokens.destroy');
    Route::post('settings/system-maintenance/super-purge', [SystemMaintenanceController::class, 'superPurge'])->name('settings.system-maintenance.super-purge');
    // لوحة الأدمن + سجل التدقيق + تقارير مركزية
    Route::get('admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('system/audit-log', [AuditLogWebController::class, 'index'])->name('system.audit.index');

    Route::get('reports/profit-loss', fn () => redirect()->route('finance.reports.profit-loss'));
    Route::middleware('module:sales')->group(function () {
        Route::get('reports/statement', [StatementReportWebController::class, 'index'])->name('reports.statement.index');
    });
    Route::middleware('module:manufacturing')->group(function () {
        Route::get('reports/production/{record}', [ProductionReportWebController::class, 'show'])->name('reports.production.show');
        Route::get('reports/production', [ProductionReportWebController::class, 'index'])->name('reports.production.index');
    });
});

/*
|--------------------------------------------------------------------------
| لوحة التحكم المركزية (Super-Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('tenants', [SuperAdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('tenants/create', [SuperAdminTenantController::class, 'create'])->name('tenants.create');
    Route::post('tenants', [SuperAdminTenantController::class, 'store'])->name('tenants.store');
    Route::get('tenants/{tenant}', [SuperAdminTenantController::class, 'show'])->whereNumber('tenant')->name('tenants.show');
    Route::put('tenants/{tenant}/modules', [SuperAdminTenantController::class, 'updateModules'])->whereNumber('tenant')->name('tenants.modules.update');
    Route::get('tenants/{tenant}/premium-features', [SuperAdminTenantController::class, 'premiumFeatures'])->whereNumber('tenant')->name('tenants.premium-features.show');
    Route::put('tenants/{tenant}/premium-features', [SuperAdminTenantController::class, 'updatePremiumFeatures'])->whereNumber('tenant')->name('tenants.premium-features.update');
    Route::put('tenants/{tenant}/slug', [SuperAdminTenantController::class, 'updateSlug'])->whereNumber('tenant')->name('tenants.slug.update');
});

/*
|--------------------------------------------------------------------------
| موديول العمليات (Operations)
|--------------------------------------------------------------------------
*/
Route::prefix('operations')->name('operations.')->middleware(['auth', 'worker.scope', 'module:manufacturing'])->group(function () {
    Route::get('dashboard', [OperationsDashboardController::class, 'index'])->name('dashboard.index');
    Route::get('production-entry', [ProductionEntryWebController::class, 'create'])->name('production-entry.create');
    Route::post('production-entry', [ProductionEntryWebController::class, 'store'])->name('production-entry.store');
    Route::get('production-entry/item-by-barcode', [ProductionEntryWebController::class, 'itemByBarcode'])->name('production-entry.item-by-barcode');
    Route::get('production-entry/item-bom-status', [ProductionEntryWebController::class, 'itemBomStatus'])->name('production-entry.item-bom-status');

    Route::middleware('role:supervisor')->group(function () {
        Route::get('shifts', [OperationsShiftController::class, 'index'])->name('shifts.index');
        Route::post('shifts', [OperationsShiftController::class, 'store'])->name('shifts.store');
        Route::post('shifts/{productionShift}/start', [OperationsShiftController::class, 'start'])->name('shifts.start');
        Route::post('shifts/{productionShift}/complete', [OperationsShiftController::class, 'complete'])->name('shifts.complete');
    });
});

/*
|--------------------------------------------------------------------------
| TEMPORARY — إزالة بعد التشغيل: تشغيل الهجرات وتفريغ الكاش (بدون مصادقة = خطر أمني)
|--------------------------------------------------------------------------
*/
Route::get('/force-deploy', function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('config:clear');
    Artisan::call('view:clear');

    return 'Success! System Updated.';
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| مسح بيانات الديمو - تشغيل يدوي لمرة واحدة
|--------------------------------------------------------------------------
*/
Route::get('/run-final-cleanup', function () {
    try {
        // تنظيف الكاش أولاً عشان السيرفر يحس بالتغيير
        Artisan::call('optimize:clear');

        // تشغيل أمر المسح الجراحي
        Artisan::call('demo:cleanup', ['--force' => true]);
        $output = Artisan::output();

        return "
            <div style='padding:20px; font-family:sans-serif;'>
                <h2 style='color:green;'>✅ Cleanup Command Executed!</h2>
                <pre style='background:#eee; padding:15px;'>$output</pre>
            </div>
        ";
    } catch (\Exception $e) {
        return '❌ Error: '.$e->getMessage();
    }
});

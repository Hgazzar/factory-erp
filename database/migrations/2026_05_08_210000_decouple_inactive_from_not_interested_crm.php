<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الترحيل السابق (phase1) نسخ حالة التشغيل إلى crm_status (inactive → not_interested).
     * الحالتان مستقلتان: إيقاف تشغيلي لا يعني «غير مهتم» تسويقياً.
     * إعادة من صُنّفوا تلقائياً كذلك إلى محتمل مع الإبقاء على status كما هو.
     */
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        if (! Schema::hasColumn('customers', 'status') || ! Schema::hasColumn('customers', 'crm_status')) {
            return;
        }

        DB::table('customers')
            ->where('status', 'inactive')
            ->where('crm_status', 'not_interested')
            ->update(['crm_status' => 'potential']);
    }

    public function down(): void
    {
        // غير قابلة للعكس بأمان (لا نعرف السجلات التي كانت not_interested حقيقية قبل التصحيح).
    }
};

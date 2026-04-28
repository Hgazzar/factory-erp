<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة حقول مرنة لربط audit_logs بأحداث الوحدات (مثل نقاط البيع) دون كسر السجلات القديمة.
 * السجلات غير المتعلقة بالأدوار تُعبَّأ بـ target_user_id = actor_id لاستيفاء القيد الحالي على الهدف.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->after('new_role');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->json('meta')->nullable()->after('subject_id');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['subject_type', 'subject_id', 'meta']);
        });
    }
};

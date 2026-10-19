<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إصلاح بيئات الإنتاج التي فاتها عمود linked_user_id بعد phase4.
 * إنشاء حساب دخول الموظف يعتمد على هذا العمود — بدونه يحدث 500 عند الربط.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (Schema::hasColumn('employees', 'linked_user_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignId('linked_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // لا نحذف العمود في down — قد يكون موجوداً من phase4 ويُستخدم فعلياً.
    }
};

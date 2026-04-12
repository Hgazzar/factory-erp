<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 4)->default(0)->after('amount');
            }

            if (! Schema::hasColumn('payments', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('reference');
            }

            if (! Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method', 20)->default('cash')->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }

            if (Schema::hasColumn('payments', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('payments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }

            if (Schema::hasColumn('payments', 'supplier_id')) {
                // الحقل موجود غالبًا مسبقًا في هذا المشروع؛ نحذفه فقط لو تمت إضافته بواسطة هذا المايجريشن.
                // الإبقاء على الحقل في down يقلل مخاطر كسر بيئة موجودة.
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إيصالات المصروفات أصبحت في جدول attachments؛ حذف العمود القديم.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasColumn('payments', 'receipt_path')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropColumn('receipt_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! Schema::hasColumn('payments', 'receipt_path')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->string('receipt_path', 500)->nullable()->after('notes');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attachments')) {
            return;
        }

        Schema::table('attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('attachments', 'file_size')) {
                $table->unsignedBigInteger('file_size')->default(0)->after('file_type')->comment('حجم الملف بالبايت');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attachments')) {
            return;
        }

        Schema::table('attachments', function (Blueprint $table) {
            if (Schema::hasColumn('attachments', 'file_size')) {
                $table->dropColumn('file_size');
            }
        });
    }
};

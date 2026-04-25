<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'code')) {
                $table->string('code', 64)->nullable()->after('name');
            }
            if (! Schema::hasColumn('departments', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('code')->constrained('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('departments', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('manager_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
            if (Schema::hasColumn('departments', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('departments', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};

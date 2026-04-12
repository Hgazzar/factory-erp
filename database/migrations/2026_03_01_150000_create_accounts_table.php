<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول شجرة الحسابات (Double-entry foundation)
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('كود الحساب');
            $table->string('name_ar')->comment('اسم الحساب بالعربي');
            $table->string('name_en')->nullable()->comment('اسم الحساب بالإنجليزي');
            $table->string('type', 30)->comment('asset | liability | expense | revenue');
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('opening_balance', 15, 4)->default(0)->comment('الرصيد الافتتاحي');
            $table->timestamps();

            $table->index(['type']);
            $table->index(['parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};


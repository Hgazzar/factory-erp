<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 30)->default('percentage')->comment('نسبة مئوية');
            $table->string('basis', 30)->default('revenue')->comment('الإيرادات');
            $table->decimal('rate_percent', 5, 2)->default(0);
            $table->decimal('min_amount', 15, 4)->nullable()->comment('الحد الأدنى لمبلغ العمولة');
            $table->decimal('max_amount', 15, 4)->nullable()->comment('الحد الأقصى لمبلغ العمولة');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('priority')->default(1)->comment('رقم أقل = أولوية أعلى');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['status', 'valid_from', 'valid_until']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};

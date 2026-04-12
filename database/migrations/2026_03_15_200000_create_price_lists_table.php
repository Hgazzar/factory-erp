<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('رمز القائمة');
            $table->string('name');
            $table->string('currency', 10)->default('SAR');
            $table->string('type', 20)->comment('sale, purchase');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->unsignedInteger('priority')->default(0)->comment('الأولوية؛ الأعلى = الافتراضي');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};

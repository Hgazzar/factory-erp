<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('اسم المنشأة');
            $table->string('tax_number')->nullable()->comment('الرقم الضريبي');
            $table->string('commercial_register')->nullable()->comment('السجل التجاري');
            $table->text('address')->nullable()->comment('العنوان');
            $table->string('logo_url')->nullable()->comment('رابط أو مسار اللوجو');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};

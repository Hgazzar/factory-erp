<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('einvoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->default('zatca')->comment('zatca = ZATCA Saudi Arabia');
            $table->string('environment', 20)->default('sandbox')->comment('sandbox|production');
            $table->unsignedTinyInteger('retry_attempts')->default(3);
            $table->unsignedTinyInteger('retry_delay_minutes')->default(0);
            $table->boolean('enabled')->default(false)->comment('تفعيل الفوترة الإلكترونية');
            $table->boolean('auto_send_on_issue')->default(false)->comment('إرسال تلقائي عند الإصدار');
            $table->string('zatca_tax_number', 20)->nullable()->comment('الرقم الضريبي 15 رقم');
            $table->string('zatca_seller_name', 255)->nullable();
            $table->string('zatca_seller_name_ar', 255)->nullable();
            $table->string('csr_path', 500)->nullable()->comment('مسار ملف CSR للربط المستقبلي');
            $table->string('private_key_path', 500)->nullable()->comment('مسار المفتاح الخاص');
            $table->string('otp', 50)->nullable()->comment('OTP للربط مع فاتورة - مؤقت');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einvoice_settings');
    }
};

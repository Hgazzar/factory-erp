<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('einvoice_settings', function (Blueprint $table) {
            $table->longText('certificate')->nullable()->after('otp')->comment('شهادة CSID (PEM) من استجابة الـ Compliance');
            $table->longText('private_key')->nullable()->after('certificate')->comment('نسخة PEM للمفتاح الخاص المستخدم في التوقيع');
            $table->string('request_id', 120)->nullable()->after('private_key')->comment('معرّف طلب الامتثال requestID من ZATCA');
            $table->string('compliance_secret', 500)->nullable()->after('request_id')->comment('السر المرفق مع CSID من استجابة الامتثال');
        });
    }

    public function down(): void
    {
        Schema::table('einvoice_settings', function (Blueprint $table) {
            $table->dropColumn(['certificate', 'private_key', 'request_id', 'compliance_secret']);
        });
    }
};

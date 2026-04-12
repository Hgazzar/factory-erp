<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سجل تدقيق ثابت (لا يُحدَّث ولا يُحذف) لتتبع تغييرات الإنتاج والمالية.
     */
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('المستخدم الذي قام بالإجراء');
            $table->string('action', 20)->comment('create|update|delete');
            $table->string('table_name', 80)->comment('اسم الجدول');
            $table->unsignedBigInteger('record_id')->nullable()->comment('معرف السجل');
            $table->json('old_values')->nullable()->comment('القيم قبل التغيير');
            $table->json('new_values')->nullable()->comment('القيم بعد التغيير');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['table_name', 'record_id']);
            $table->index(['user_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};

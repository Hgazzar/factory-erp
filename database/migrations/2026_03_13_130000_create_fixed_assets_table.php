<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique();
            $table->string('name');
            $table->string('category', 100);
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 14, 2)->default(0);
            $table->decimal('book_value', 14, 2)->default(0);
            $table->enum('status', ['in_use', 'stopped', 'decommissioned'])->default('in_use');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};

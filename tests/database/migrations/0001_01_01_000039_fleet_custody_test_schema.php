<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_custody_issues')) {
            Schema::create('fleet_custody_issues', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agent_id')->constrained('fleet_agents')->cascadeOnDelete();
                $table->string('issue_number', 32);
                $table->date('issued_on');
                $table->string('status', 24)->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'issue_number']);
            });
        }

        if (! Schema::hasTable('fleet_custody_issue_lines')) {
            Schema::create('fleet_custody_issue_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('issue_id')->constrained('fleet_custody_issues')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('fleet_products')->cascadeOnDelete();
                $table->decimal('quantity', 14, 4);
                $table->decimal('unit_price', 14, 4)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_custody_issue_lines');
        Schema::dropIfExists('fleet_custody_issues');
    }
};

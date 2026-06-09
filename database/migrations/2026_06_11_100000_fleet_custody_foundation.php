<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_custody_issues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('issue_number', 32);
            $table->date('issued_on');
            $table->string('status', 24)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('fleet_agents')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['user_id', 'issue_number']);
            $table->index(['user_id', 'agent_id', 'status']);
            $table->index(['user_id', 'issued_on']);
        });

        Schema::create('fleet_custody_issue_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('issue_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('issue_id')->references('id')->on('fleet_custody_issues')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('fleet_products')->cascadeOnDelete();
            $table->index(['issue_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_custody_issue_lines');
        Schema::dropIfExists('fleet_custody_issues');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local schema repair: expenses module columns/tables missing from factory_erp drift.
 * Idempotent — safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
                $table->boolean('is_taxable')->default(false);
                $table->string('status', 20)->default('active');
                $table->timestamps();
                $table->unique(['user_id', 'code']);
            });
        } elseif (! Schema::hasColumn('expense_categories', 'user_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('cost_centers')) {
            Schema::create('cost_centers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name');
                $table->string('branch', 100)->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
                $table->decimal('annual_budget', 14, 2)->default(0);
                $table->decimal('monthly_budget', 14, 2)->default(0);
                $table->string('status', 20)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'code']);
            });
        } elseif (! Schema::hasColumn('cost_centers', 'user_id')) {
            Schema::table('cost_centers', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('bank_name', 150);
                $table->string('branch_name', 150)->nullable();
                $table->string('account_number', 100);
                $table->string('iban', 34)->nullable();
                $table->string('currency', 3)->default('SAR');
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->string('status', 20)->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'account_number']);
            });
        } elseif (! Schema::hasColumn('bank_accounts', 'user_id')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'expense_account_id')) {
                $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 4)->default(0);
            }
            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('payments', 'expense_number')) {
                $table->string('expense_number', 50)->nullable();
            }
            if (! Schema::hasColumn('payments', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable();
            }
            if (! Schema::hasColumn('payments', 'total_amount')) {
                $table->decimal('total_amount', 15, 4)->default(0);
            }
            if (! Schema::hasColumn('payments', 'status')) {
                $table->string('status', 30)->default('draft');
            }
            if (! Schema::hasColumn('payments', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'expense_category_id')) {
                $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            }
        });

        // Unique expense number per tenant when column exists.
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique(['user_id', 'expense_number'], 'payments_user_expense_number_unique');
            });
        } catch (\Throwable) {
            // Index may already exist.
        }
    }

    public function down(): void
    {
        // Non-destructive repair — no down.
    }
};

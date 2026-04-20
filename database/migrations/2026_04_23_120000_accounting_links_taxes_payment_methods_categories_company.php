<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->decimal('rate_percent', 8, 4)->default(0);
                $table->foreignId('ledger_account_id')->constrained('accounts')->restrictOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'code']);
            });
        }

        if (! Schema::hasTable('payment_method_accounts')) {
            Schema::create('payment_method_accounts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('method_key', 20);
                $table->foreignId('ledger_account_id')->constrained('accounts')->restrictOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'method_key']);
            });
        }

        if (Schema::hasTable('item_categories')) {
            Schema::table('item_categories', function (Blueprint $table): void {
                if (! Schema::hasColumn('item_categories', 'inventory_account_id')) {
                    $table->foreignId('inventory_account_id')->nullable()->after('is_active')->constrained('accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn('item_categories', 'sales_income_account_id')) {
                    $table->foreignId('sales_income_account_id')->nullable()->after('inventory_account_id')->constrained('accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn('item_categories', 'cogs_account_id')) {
                    $table->foreignId('cogs_account_id')->nullable()->after('sales_income_account_id')->constrained('accounts')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('company_settings')) {
            Schema::table('company_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('company_settings', 'default_receivable_account_id')) {
                    $table->foreignId('default_receivable_account_id')->nullable()->after('default_vat_percent')->constrained('accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn('company_settings', 'default_payable_account_id')) {
                    $table->foreignId('default_payable_account_id')->nullable()->after('default_receivable_account_id')->constrained('accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn('company_settings', 'purchase_discount_ledger_account_id')) {
                    $table->foreignId('purchase_discount_ledger_account_id')->nullable()->after('default_payable_account_id')->constrained('accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn('company_settings', 'sales_allowed_discount_ledger_account_id')) {
                    $table->foreignId('sales_allowed_discount_ledger_account_id')->nullable()->after('purchase_discount_ledger_account_id')->constrained('accounts')->nullOnDelete();
                }
            });
        }

        $this->seedDefaultsPerUser();
    }

    private function seedDefaultsPerUser(): void
    {
        foreach (DB::table('users')->pluck('id') as $uid) {
            $uid = (int) $uid;
            if (! Schema::hasTable('tax_rates')) {
                break;
            }
            if (DB::table('tax_rates')->where('user_id', $uid)->where('code', 'VAT')->exists()) {
                continue;
            }
            $vatAccount = DB::table('accounts')->where('user_id', $uid)->where('code', '2030')->value('id');
            if (! $vatAccount) {
                continue;
            }
            DB::table('tax_rates')->insert([
                'user_id' => $uid,
                'code' => 'VAT',
                'name_ar' => 'ضريبة القيمة المضافة',
                'name_en' => 'VAT',
                'rate_percent' => 15,
                'ledger_account_id' => (int) $vatAccount,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('payment_method_accounts')) {
            return;
        }

        foreach (DB::table('users')->pluck('id') as $uid) {
            $uid = (int) $uid;
            $cashId = DB::table('accounts')->where('user_id', $uid)->where('code', '1010')->value('id');
            $bankId = DB::table('accounts')->where('user_id', $uid)->where('code', '1020')->value('id');
            if (! $cashId || ! $bankId) {
                continue;
            }
            foreach (['cash' => (int) $cashId, 'transfer' => (int) $bankId, 'card' => (int) $bankId] as $key => $accId) {
                if (DB::table('payment_method_accounts')->where('user_id', $uid)->where('method_key', $key)->exists()) {
                    continue;
                }
                DB::table('payment_method_accounts')->insert([
                    'user_id' => $uid,
                    'method_key' => $key,
                    'ledger_account_id' => $accId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('item_categories') && Schema::hasTable('accounts')) {
            $uid = (int) (DB::table('users')->orderBy('id')->value('id') ?? 1);
            $inv = DB::table('accounts')->where('user_id', $uid)->where('code', '1041')->value('id');
            $rev = DB::table('accounts')->where('user_id', $uid)->where('code', '4000')->value('id');
            $cogs = DB::table('accounts')->where('user_id', $uid)->where('code', '5000')->value('id');
            if ($inv && $rev && $cogs) {
                DB::table('item_categories')->whereNull('inventory_account_id')->update([
                    'inventory_account_id' => $inv,
                    'sales_income_account_id' => $rev,
                    'cogs_account_id' => $cogs,
                ]);
            }
        }

        if (Schema::hasTable('company_settings') && Schema::hasTable('accounts')) {
            $uid = (int) (DB::table('users')->orderBy('id')->value('id') ?? 1);
            $ar = DB::table('accounts')->where('user_id', $uid)->where('code', '1030')->value('id');
            $ap = DB::table('accounts')->where('user_id', $uid)->where('code', '2010')->value('id');
            $purDisc = DB::table('accounts')->where('user_id', $uid)->where('code', '5050')->value('id');
            $salesDisc = DB::table('accounts')->where('user_id', $uid)->where('code', '4050')->value('id');
            $row = DB::table('company_settings')->orderBy('id')->first();
            if ($row && $ar && $ap && $purDisc && $salesDisc) {
                DB::table('company_settings')->where('id', $row->id)->update([
                    'default_receivable_account_id' => $ar,
                    'default_payable_account_id' => $ap,
                    'purchase_discount_ledger_account_id' => $purDisc,
                    'sales_allowed_discount_ledger_account_id' => $salesDisc,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_settings')) {
            Schema::table('company_settings', function (Blueprint $table): void {
                foreach ([
                    'default_receivable_account_id',
                    'default_payable_account_id',
                    'purchase_discount_ledger_account_id',
                    'sales_allowed_discount_ledger_account_id',
                ] as $col) {
                    if (Schema::hasColumn('company_settings', $col)) {
                        $table->dropConstrainedForeignId($col);
                    }
                }
            });
        }

        if (Schema::hasTable('item_categories')) {
            Schema::table('item_categories', function (Blueprint $table): void {
                foreach (['inventory_account_id', 'sales_income_account_id', 'cogs_account_id'] as $col) {
                    if (Schema::hasColumn('item_categories', $col)) {
                        $table->dropConstrainedForeignId($col);
                    }
                }
            });
        }

        Schema::dropIfExists('payment_method_accounts');
        Schema::dropIfExists('tax_rates');
    }
};

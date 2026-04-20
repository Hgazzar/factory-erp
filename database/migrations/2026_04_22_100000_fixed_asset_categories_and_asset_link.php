<?php

use App\Support\DefaultLedgerAccounts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fixed_asset_categories')) {
            Schema::create('fixed_asset_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->foreignId('ledger_asset_account_id')->constrained('accounts')->restrictOnDelete();
                $table->foreignId('ledger_depreciation_cost_account_id')->constrained('accounts')->restrictOnDelete();
                $table->foreignId('ledger_accumulated_depreciation_account_id')->constrained('accounts')->restrictOnDelete();
                $table->string('status', 16)->default('active');
                $table->timestamps();

                $table->unique(['user_id', 'code']);
            });
        }

        if (Schema::hasTable('fixed_assets') && ! Schema::hasColumn('fixed_assets', 'fixed_asset_category_id')) {
            Schema::table('fixed_assets', function (Blueprint $table): void {
                $table->foreignId('fixed_asset_category_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('fixed_asset_categories')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('fixed_asset_categories') || ! Schema::hasTable('fixed_assets')) {
            return;
        }

        $userIds = DB::table('users')->pluck('id')->map(fn ($id) => (int) $id)->all();

        $defaultCategoryIdByUser = [];
        foreach ($userIds as $uid) {
            $assetAcc = DefaultLedgerAccounts::fixedAssetPostingAccount($uid);
            $depExp = DefaultLedgerAccounts::depreciationExpenseAccount($uid);
            $accDep = DefaultLedgerAccounts::accumulatedDepreciationAccount($uid);

            $existing = DB::table('fixed_asset_categories')
                ->where('user_id', $uid)
                ->where('code', 'FACAT-DEFAULT')
                ->value('id');

            if ($existing) {
                $defaultCategoryIdByUser[$uid] = (int) $existing;

                continue;
            }

            $id = DB::table('fixed_asset_categories')->insertGetId([
                'user_id' => $uid,
                'code' => 'FACAT-DEFAULT',
                'name_ar' => 'فئة افتراضية',
                'name_en' => 'Default category',
                'ledger_asset_account_id' => $assetAcc->id,
                'ledger_depreciation_cost_account_id' => $depExp->id,
                'ledger_accumulated_depreciation_account_id' => $accDep->id,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $defaultCategoryIdByUser[$uid] = $id;
        }

        if ($defaultCategoryIdByUser !== []) {
            $fallbackUid = (int) array_key_first($defaultCategoryIdByUser);
            $fallbackCatId = $defaultCategoryIdByUser[$fallbackUid];

            foreach (DB::table('fixed_assets')->select('id', 'cost_center_id', 'category_id', 'ledger_account_id')->get() as $row) {
                $uid = $fallbackUid;
                if ($row->cost_center_id) {
                    $c = DB::table('cost_centers')->where('id', $row->cost_center_id)->value('user_id');
                    if ($c) {
                        $uid = (int) $c;
                    }
                } elseif ($row->category_id) {
                    $c = DB::table('expense_categories')->where('id', $row->category_id)->value('user_id');
                    if ($c) {
                        $uid = (int) $c;
                    }
                }

                if (! isset($defaultCategoryIdByUser[$uid])) {
                    $uid = $fallbackUid;
                }

                $catId = $defaultCategoryIdByUser[$uid];
                $ledgerId = $row->ledger_account_id
                    ? (int) $row->ledger_account_id
                    : (int) DB::table('fixed_asset_categories')->where('id', $catId)->value('ledger_asset_account_id');

                DB::table('fixed_assets')->where('id', $row->id)->update([
                    'fixed_asset_category_id' => $catId,
                    'ledger_account_id' => $ledgerId,
                ]);
            }
        }

        if (Schema::hasColumn('fixed_assets', 'category_id')) {
            Schema::table('fixed_assets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('category_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fixed_assets') && Schema::hasColumn('fixed_assets', 'fixed_asset_category_id')) {
            Schema::table('fixed_assets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('fixed_asset_category_id');
            });
        }

        if (Schema::hasTable('fixed_assets') && ! Schema::hasColumn('fixed_assets', 'category_id')) {
            Schema::table('fixed_assets', function (Blueprint $table): void {
                $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            });
        }

        Schema::dropIfExists('fixed_asset_categories');
    }
};

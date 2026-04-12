<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->alterSuppliers();
        $this->alterExpenseCategories();
        $this->alterPayments();
        $this->alterSalesOrders();
        $this->alterQuotations();
        $this->alterAccounts();
    }

    public function down(): void
    {
        $this->revertAccounts();
        $this->revertQuotations();
        $this->revertSalesOrders();
        $this->revertPayments();
        $this->revertExpenseCategories();
        $this->revertSuppliers();
    }

    private function alterSuppliers(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->unique(['user_id', 'code']);
        });
    }

    private function alterExpenseCategories(): void
    {
        if (! Schema::hasTable('expense_categories')) {
            return;
        }

        Schema::table('expense_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_categories', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unique(['user_id', 'code']);
        });
    }

    private function alterPayments(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        if (Schema::hasColumn('payments', 'expense_number')) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropUnique(['expense_number']);
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS payments_expense_number_unique');
                } catch (\Throwable) {
                    //
                }
            }

            Schema::table('payments', function (Blueprint $table) {
                $table->unique(['user_id', 'expense_number'], 'payments_user_expense_number_unique');
            });
        }
    }

    private function alterSalesOrders(): void
    {
        if (! Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        if (Schema::hasColumn('sales_orders', 'order_number')) {
            try {
                Schema::table('sales_orders', function (Blueprint $table) {
                    $table->dropUnique(['order_number']);
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS sales_orders_order_number_unique');
                } catch (\Throwable) {
                    //
                }
            }

            foreach (DB::table('sales_orders')->select('id')->orderBy('id')->get() as $row) {
                DB::table('sales_orders')->where('id', $row->id)->update([
                    'order_number' => 'SO-'.$row->id,
                ]);
            }

            Schema::table('sales_orders', function (Blueprint $table) {
                $table->unique(['user_id', 'order_number'], 'sales_orders_user_order_number_unique');
            });
        }
    }

    private function alterQuotations(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        if (! Schema::hasColumn('quotations', 'quotation_number')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('quotation_number', 50)->nullable()->after('user_id');
            });
        }

        foreach (DB::table('quotations')->select('id', 'quotation_number')->orderBy('id')->get() as $row) {
            if ($row->quotation_number === null || $row->quotation_number === '') {
                DB::table('quotations')->where('id', $row->id)->update([
                    'quotation_number' => 'QT-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->unique(['user_id', 'quotation_number'], 'quotations_user_quotation_number_unique');
        });
    }

    private function alterAccounts(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->unique(['user_id', 'code'], 'accounts_user_code_unique');
        });
    }

    private function revertSuppliers(): void
    {
        if (! Schema::hasTable('suppliers') || ! Schema::hasColumn('suppliers', 'user_id')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'code']);
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unique('code');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    private function revertExpenseCategories(): void
    {
        if (! Schema::hasTable('expense_categories') || ! Schema::hasColumn('expense_categories', 'user_id')) {
            return;
        }

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'code']);
        });
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unique('code');
        });
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    private function revertPayments(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'user_id')) {
            return;
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_user_expense_number_unique');
            });
        } catch (\Throwable) {
            //
        }

        if (Schema::hasColumn('payments', 'expense_number')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('expense_number');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    private function revertSalesOrders(): void
    {
        if (! Schema::hasTable('sales_orders') || ! Schema::hasColumn('sales_orders', 'user_id')) {
            return;
        }

        try {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropUnique('sales_orders_user_order_number_unique');
            });
        } catch (\Throwable) {
            //
        }

        if (Schema::hasColumn('sales_orders', 'order_number')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->unique('order_number');
            });
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    private function revertQuotations(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        try {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropUnique('quotations_user_quotation_number_unique');
            });
        } catch (\Throwable) {
            //
        }

        if (Schema::hasColumn('quotations', 'quotation_number')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropColumn('quotation_number');
            });
        }

        if (Schema::hasColumn('quotations', 'user_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }

    private function revertAccounts(): void
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasColumn('accounts', 'user_id')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique('accounts_user_code_unique');
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->unique('code');
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ترقية مخطط قديم (payrolls + payroll_items كصف لكل موظف) إلى
 * payroll_cycles + pay_slips + payroll_items (بنود تفصيلية).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_cycles')) {
            return;
        }

        if (! Schema::hasTable('payrolls')) {
            return;
        }

        $hadLegacyItemTable = Schema::hasTable('payroll_items');

        Schema::rename('payrolls', 'payroll_cycles');

        Schema::table('payroll_cycles', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_cycles', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('year');
            }
            if (! Schema::hasColumn('payroll_cycles', 'employees_count')) {
                $table->unsignedInteger('employees_count')->default(0)->after('payment_date');
            }
            if (! Schema::hasColumn('payroll_cycles', 'total_gross')) {
                $table->decimal('total_gross', 15, 2)->default(0)->after('status');
            }
            if (! Schema::hasColumn('payroll_cycles', 'total_deductions')) {
                $table->decimal('total_deductions', 15, 2)->default(0)->after('total_gross');
            }
        });

        if (! $hadLegacyItemTable) {
            $this->createPaySlipsAndLineItemsTables();

            return;
        }

        Schema::rename('payroll_items', 'legacy_payroll_rows');

        $this->createPaySlipsAndLineItemsTables();

        if (Schema::hasTable('legacy_payroll_rows')) {
            $allowCol = Schema::hasColumn('legacy_payroll_rows', 'allowances') ? 'allowances' : 'total_allowances';

            $legacyRows = DB::table('legacy_payroll_rows')->get();
            foreach ($legacyRows as $r) {
                $att = (float) ($r->attendance_deductions ?? 0);
                $oth = (float) ($r->other_deductions ?? 0);
                DB::table('pay_slips')->insert([
                    'payroll_cycle_id' => (int) $r->payroll_id,
                    'employee_id' => (int) $r->employee_id,
                    'basic_salary' => (float) ($r->basic_salary ?? 0),
                    'total_allowances' => (float) ($r->{$allowCol === 'allowances' ? 'allowances' : 'total_allowances'} ?? 0),
                    'attendance_deductions' => $att,
                    'statutory_deductions' => $oth,
                    'total_deductions' => round($att + $oth, 2),
                    'net_salary' => (float) ($r->net_salary ?? 0),
                    'overtime_hours' => 0,
                    'overtime_amount' => 0,
                    'absence_hours' => 0,
                    'late_hours' => 0,
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ]);
            }

            Schema::drop('legacy_payroll_rows');
        }

        $slips = DB::table('pay_slips')->get();
        foreach ($slips as $slip) {
            $sid = (int) $slip->id;
            $sort = 0;
            $rows = [];
            if ((float) $slip->basic_salary > 0) {
                $rows[] = ['basic_salary', 'earning', 'الراتب الأساسي', (float) $slip->basic_salary, $sort++];
            }
            if ((float) $slip->total_allowances > 0) {
                $rows[] = ['other_allowance', 'earning', 'البدلات', (float) $slip->total_allowances, $sort++];
            }
            if ((float) $slip->attendance_deductions > 0) {
                $rows[] = ['attendance_deduction', 'deduction', 'خصومات حضور', (float) $slip->attendance_deductions, $sort++];
            }
            if ((float) $slip->statutory_deductions > 0) {
                $rows[] = ['insurance', 'deduction', 'تأمينات وضريبة', (float) $slip->statutory_deductions, $sort++];
            }
            foreach ($rows as [$code, $kind, $label, $amount, $ord]) {
                DB::table('payroll_items')->insert([
                    'pay_slip_id' => $sid,
                    'item_code' => $code,
                    'item_kind' => $kind,
                    'label' => $label,
                    'amount' => $amount,
                    'sort_order' => $ord,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $cycles = DB::table('payroll_cycles')->select('id')->get();
        foreach ($cycles as $c) {
            $cid = (int) $c->id;
            $agg = DB::table('pay_slips')->where('payroll_cycle_id', $cid)
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(basic_salary + total_allowances + overtime_amount),0) as g, COALESCE(SUM(total_deductions),0) as d')
                ->first();
            DB::table('payroll_cycles')->where('id', $cid)->update([
                'employees_count' => (int) ($agg->cnt ?? 0),
                'total_gross' => round((float) ($agg->g ?? 0), 2),
                'total_deductions' => round((float) ($agg->d ?? 0), 2),
            ]);
        }
    }

    private function createPaySlipsAndLineItemsTables(): void
    {
        if (Schema::hasTable('pay_slips')) {
            return;
        }

        Schema::create('pay_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cycle_id')->constrained('payroll_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('attendance_deductions', 15, 2)->default(0);
            $table->decimal('statutory_deductions', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->decimal('absence_hours', 10, 2)->default(0);
            $table->decimal('late_hours', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_cycle_id', 'employee_id']);
        });

        if (! Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pay_slip_id')->constrained('pay_slips')->cascadeOnDelete();
                $table->string('item_code', 64);
                $table->string('item_kind', 16);
                $table->string('label', 255)->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['pay_slip_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        //
    }
};

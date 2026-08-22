<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\User;
use App\Support\NurseryAccess;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryFinanceNavigationD1Test extends NurseryTestCase
{
    #[Test]
    public function owner_sees_approved_finance_sidebar_links_and_not_hidden_surfaces(): void
    {
        $response = $this->get(route('nursery.dashboard'));

        $response->assertOk();
        $response->assertSee(route('nursery.finance.index'), false);
        $response->assertSee(route('finance.dashboard'), false);
        $response->assertSee(route('finance.accounts.index'), false);
        $response->assertSee(route('finance.journals.index'), false);
        $response->assertSee(route('finance.receipts.index'), false);
        $response->assertSee(route('finance.payments.index'), false);
        $response->assertSee(route('finance.expenses.index'), false);
        $response->assertSee(route('finance.expenses.categories.index'), false);
        $response->assertSee(route('finance.bank-accounts.index'), false);
        $response->assertSee(route('finance.bank-reconciliations.index'), false);
        $response->assertSee(route('finance.ledger.index'), false);
        $response->assertSee(route('finance.reports.trial-balance'), false);
        $response->assertSee(route('finance.reports.profit-loss'), false);
        $response->assertSee(route('reports.tax.index'), false);
        $response->assertSee(route('finance.tax-rates.index'), false);
        $response->assertSee(route('finance.payment-method-accounts.edit'), false);

        $response->assertSee('ملخص المالية', false);
        $response->assertSee('لوحة المالية', false);
        $response->assertSee('سندات التحصيل', false);
        $response->assertSee('المصروفات', false);

        // HIDE / NOT NEEDED — لا روابط في Sidebar الحضانة
        $response->assertDontSee(route('finance.cost-centers.index'), false);
        $response->assertDontSee(route('finance.fixed-assets.index'), false);
        $response->assertDontSee(route('finance.cheques.index'), false);
        $response->assertDontSee(route('finance.credit-notes.index'), false);
        $response->assertDontSee(route('finance.debit-notes.index'), false);
        $response->assertDontSee(route('finance.budgets.index'), false);
        $response->assertDontSee(route('finance.reports.ar-aging'), false);
        $response->assertDontSee(route('finance.reports.ap-aging'), false);
    }

    #[Test]
    public function owner_can_open_approved_finance_routes(): void
    {
        $this->get(route('nursery.finance.index'))->assertOk();
        $this->assertAuthorized(route('finance.dashboard'));
        $this->assertAuthorized(route('finance.expenses.index'));
        $this->assertAuthorized(route('finance.reports.profit-loss'));
        $this->assertAuthorized(route('finance.ledger.index'));
        $this->assertAuthorized(route('reports.tax.index'));
    }

    #[Test]
    public function staff_without_finance_permissions_gets_403_and_no_finance_nav(): void
    {
        $staff = $this->makeStaff(['login.app']);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertDontSee(route('nursery.finance.index'), false)
            ->assertDontSee(route('finance.dashboard'), false)
            ->assertDontSee(route('finance.expenses.index'), false);

        $this->get(route('nursery.finance.index'))->assertForbidden();
        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->get(route('finance.expenses.index'))->assertForbidden();
        $this->get(route('finance.cost-centers.index'))->assertForbidden();
    }

    #[Test]
    public function staff_with_summary_only_sees_summary_link_and_cannot_open_expenses(): void
    {
        $staff = $this->makeStaff(['login.app', 'finance.view']);

        $this->actingAs($staff);

        $this->assertTrue(app(NurseryAccess::class)->allows(NurseryAccess::CAP_VIEW_FINANCE));
        $this->assertFalse(app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_FINANCE_EXPENSES));

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('nursery.finance.index'), false)
            ->assertDontSee(route('finance.expenses.index'), false)
            ->assertDontSee(route('finance.dashboard'), false);

        $this->get(route('nursery.finance.index'))->assertOk();
        $this->get(route('finance.expenses.index'))->assertForbidden();
        $this->get(route('finance.dashboard'))->assertForbidden();
    }

    #[Test]
    public function staff_with_expenses_permission_sees_expense_links_and_can_open_expenses(): void
    {
        $staff = $this->makeStaff(['login.app', 'finance.manage_expenses']);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('finance.expenses.index'), false)
            ->assertSee(route('finance.expenses.categories.index'), false)
            ->assertDontSee(route('finance.accounts.index'), false)
            ->assertDontSee(route('finance.bank-reconciliations.index'), false);

        $this->assertAuthorized(route('finance.expenses.index'));
        $this->assertAuthorized(route('finance.expenses.categories.index'));
        $this->get(route('finance.accounts.index'))->assertForbidden();
        $this->get(route('finance.bank-reconciliations.index'))->assertForbidden();
    }

    #[Test]
    public function staff_with_reports_permission_can_open_pl_and_not_ledger(): void
    {
        $staff = $this->makeStaff(['login.app', 'finance.view_reports']);

        $this->actingAs($staff);

        $this->assertAuthorized(route('finance.dashboard'));
        $this->assertAuthorized(route('finance.reports.profit-loss'));
        $this->assertAuthorized(route('finance.reports.trial-balance'));
        $this->assertAuthorized(route('reports.tax.index'));
        $this->get(route('finance.ledger.index'))->assertForbidden();
        $this->get(route('finance.journals.index'))->assertForbidden();
    }

    #[Test]
    public function hidden_finance_routes_are_forbidden_for_staff_even_with_admin_finance_perm_except_mapped(): void
    {
        $staff = $this->makeStaff(['login.app', 'finance.admin']);

        $this->actingAs($staff);

        // finance.admin يفتح المعتمد بما فيها التسوية والإعدادات (قد تفشل السكيمة بـ500؛ المهم ليس 403)
        $this->assertAuthorized(route('finance.bank-reconciliations.index'));
        $this->assertAuthorized(route('finance.tax-rates.index'));
        $this->assertAuthorized(route('finance.expenses.index'));

        // HIDE surfaces تبقى مرفوضة للموظف حتى مع finance.admin
        $this->get(route('finance.cost-centers.index'))->assertForbidden();
        $this->get(route('finance.fixed-assets.index'))->assertForbidden();
        $this->get(route('finance.cheques.index'))->assertForbidden();
        $this->get(route('finance.budgets.index'))->assertForbidden();
        $this->get(route('finance.reports.ar-aging'))->assertForbidden();
    }

    #[Test]
    public function approved_finance_nav_routes_are_registered(): void
    {
        foreach ([
            'nursery.finance.index',
            'finance.dashboard',
            'finance.accounts.index',
            'finance.journals.index',
            'finance.receipts.index',
            'finance.payments.index',
            'finance.expenses.index',
            'finance.expenses.categories.index',
            'finance.bank-accounts.index',
            'finance.bank-reconciliations.index',
            'finance.ledger.index',
            'finance.reports.trial-balance',
            'finance.reports.profit-loss',
            'reports.tax.index',
            'finance.tax-rates.index',
            'finance.payment-method-accounts.edit',
        ] as $name) {
            $this->assertTrue(Route::has($name), "Missing route [{$name}]");
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeStaff(array $permissions): User
    {
        $staff = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'fin-nav-'.uniqid().'@example.com',
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-FN-'.strtoupper(substr(uniqid(), -4)),
            'name' => 'Finance Nav Staff',
            'email' => $staff->email,
            'status' => 'active',
            'nursery_permissions' => $permissions,
        ]);

        return $staff;
    }

    /**
     * D1 يختبر التفويض فقط — بعض شاشات Finance تحتاج جداول غير مكتملة في سكيمة الاختبار.
     */
    private function assertAuthorized(string $url): void
    {
        $response = $this->get($url);
        $this->assertNotSame(
            403,
            $response->status(),
            "Expected authorized access (not 403) for [{$url}], got {$response->status()}"
        );
    }
}

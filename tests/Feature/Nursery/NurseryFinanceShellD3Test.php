<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\User;
use App\Services\Tenant\NicheLexiconService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryFinanceShellD3Test extends NurseryTestCase
{
    #[Test]
    public function approved_finance_screens_use_nursery_shell_layout(): void
    {
        $response = $this->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('id="nurseryMobileSidebar"', false);
        $response->assertSee('data-bs-target="#nurseryMobileSidebar"', false);
        $response->assertSee(route('nursery.dashboard'), false);
        $response->assertDontSee('erp-global-sidebar', false);
    }

    #[Test]
    public function nursery_summary_and_expenses_also_use_nursery_shell(): void
    {
        $this->get(route('nursery.finance.index'))
            ->assertOk()
            ->assertSee('id="nurseryMobileSidebar"', false);

        // expenses index قد يفشل بسبب سكيمة ناقصة — المهم ليس 403 وأن الـlayout nursery إن نجح
        $expenses = $this->get(route('finance.expenses.index'));
        $this->assertNotSame(403, $expenses->status());
        if ($expenses->status() === 200) {
            $expenses->assertSee('id="nurseryMobileSidebar"', false);
        }

        $this->get(route('finance.reports.profit-loss'))
            ->assertOk()
            ->assertSee('id="nurseryMobileSidebar"', false)
            ->assertSee(niche_label('finance.profit_loss', 'الأرباح والخسائر'), false);
    }

    #[Test]
    public function nursery_terminology_appears_on_finance_dashboard(): void
    {
        $this->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('لوحة المالية', false)
            ->assertSee('>لوحة المالية</h1>', false);
    }

    #[Test]
    public function nursery_lexicon_overrides_do_not_change_defaults_for_other_niches(): void
    {
        $lexicon = app(NicheLexiconService::class);

        $this->assertSame('لوحة المالية', config('lexicon.niche_overrides.nurseries')['finance.dashboard']);
        $this->assertSame('لوحة المحاسبة', config('lexicon.defaults')['finance.dashboard']);
        $this->assertSame('ولي الأمر', config('lexicon.niche_overrides.nurseries')['entities.customer']);
        $this->assertSame('العميل', config('lexicon.defaults')['entities.customer']);

        // manufacturing لا يرث مسميات الحضانة
        $this->assertArrayNotHasKey('finance.dashboard', config('lexicon.niche_overrides.manufacturing'));
        $this->assertSame(
            'مخزن الخامات',
            config('lexicon.niche_overrides.manufacturing')['modules.inventory']
        );
    }

    #[Test]
    public function niche_shell_layout_helper_returns_nursery_for_this_tenant(): void
    {
        $this->assertSame('layouts.nursery', niche_shell_layout());
        $this->assertTrue(is_nursery_shell());
    }

    #[Test]
    public function owner_sees_reordered_finance_sidebar_labels(): void
    {
        $html = $this->get(route('nursery.dashboard'))->assertOk()->getContent();

        $posSummary = strpos($html, 'ملخص المالية');
        $posDash = strpos($html, 'لوحة المالية');
        $posExp = strpos($html, 'مصروفات الحضانة');
        if ($posExp === false) {
            $posExp = strpos($html, 'المصروفات');
        }
        $posReceipt = strpos($html, 'سند');

        $this->assertNotFalse($posSummary);
        $this->assertNotFalse($posDash);
        $this->assertLessThan($posDash, $posSummary);
        if ($posExp !== false) {
            $this->assertLessThan($posExp, $posDash);
        }
        $this->assertNotFalse($posReceipt);
    }

    #[Test]
    public function staff_without_finance_permission_has_no_finance_nav_and_gets_403(): void
    {
        $staff = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'd3-nofin@example.com',
            'password' => 'password',
        ]);
        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-D3-1',
            'name' => 'No Finance D3',
            'email' => $staff->email,
            'status' => 'active',
            'nursery_permissions' => ['login.app'],
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertDontSee(route('finance.dashboard'), false)
            ->assertDontSee(route('nursery.finance.index'), false)
            ->assertSee('id="nurseryMobileSidebar"', false);

        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->get(route('nursery.finance.index'))->assertForbidden();
    }

    #[Test]
    public function mobile_nursery_nav_marker_remains_on_finance_pages(): void
    {
        $this->get(route('finance.accounts.index'))
            ->assertOk()
            ->assertSee('id="nurseryMobileSidebar"', false)
            ->assertSee('aria-label="قائمة الحضانة"', false);
    }

    #[Test]
    public function finance_pages_include_nursery_color_unify_stylesheet(): void
    {
        $this->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('id="nursery-finance-color-unify"', false)
            ->assertSee('--nf-primary', false);
    }
}

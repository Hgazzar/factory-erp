<?php

namespace App\Http\Controllers;

use App\Models\CrmActivity;
use App\Models\CrmAppointment;
use App\Models\CrmLoyaltyAccount;
use App\Models\CrmOpportunity;
use App\Models\CrmSegment;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CrmDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $tenantId = (int) $user->id;

        $potentialCount = Customer::queryForCrmUser($user)
            ->where('customers.crm_status', 'potential')
            ->count();

        $openPipelineValue = (float) CrmOpportunity::forTenant($tenantId)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->sum('estimated_value');

        $closedDealsThisMonth = CrmOpportunity::forTenant($tenantId)
            ->where('stage', 'closed_won')
            ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $totalSegments = CrmSegment::query()
            ->where('user_id', $tenantId)
            ->count();

        $salesFunnel = [
            'draft' => CrmOpportunity::forTenant($tenantId)->where('stage', 'draft')->count(),
            'negotiation' => CrmOpportunity::forTenant($tenantId)->where('stage', 'negotiation')->count(),
            'closing' => CrmOpportunity::forTenant($tenantId)->whereIn('stage', ['closed_won', 'closed_lost'])->count(),
        ];

        $latestLeads = Customer::queryForCrmUser($user)
            ->where('customers.crm_status', 'potential')
            ->orderByDesc('customers.created_at')
            ->limit(5)
            ->get();

        $opportunitiesCount = CrmOpportunity::forTenant($tenantId)->count();
        $openOpportunitiesCount = CrmOpportunity::forTenant($tenantId)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->count();
        $appointmentsToday = CrmAppointment::forTenant($tenantId)
            ->whereDate('start_at', today())
            ->count();
        $activitiesCount = Schema::hasTable('crm_activities')
            ? CrmActivity::query()->where('user_id', $tenantId)->count()
            : 0;
        $loyaltyAccountsCount = Schema::hasTable('crm_loyalty_accounts')
            ? CrmLoyaltyAccount::query()->forTenant($tenantId)->count()
            : 0;
        $totalCustomersCount = Customer::queryForCrmUser($user)->count();

        $trendLabels = [];
        $trendSeries = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $trendLabels[] = $month->format('M');
            $trendSeries[] = Customer::queryForCrmUser($user)
                ->whereBetween('customers.created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        }

        $statusLegend = [
            'potential' => 'محتمل',
            'active' => 'نشط',
            'inactive' => 'غير نشط',
        ];
        $statusLabels = array_values($statusLegend);
        $statusSeries = [
            Customer::queryForCrmUser($user)->where('customers.crm_status', 'potential')->count(),
            Customer::queryForCrmUser($user)->where('customers.crm_status', 'active')->count(),
            Customer::queryForCrmUser($user)->where(function ($q) {
                $q->where('customers.crm_status', 'inactive')
                    ->orWhere('customers.crm_status', 'not_interested');
            })->count(),
        ];
        $chartPalette = [
            'trend_line' => '#2563EB',
            'trend_fill' => 'rgba(37, 99, 235, 0.12)',
            'status' => ['#60A5FA', '#34D399', '#9CA3AF'],
        ];

        return view('crm.dashboard', compact(
            'potentialCount',
            'openPipelineValue',
            'closedDealsThisMonth',
            'totalSegments',
            'salesFunnel',
            'latestLeads',
            'opportunitiesCount',
            'openOpportunitiesCount',
            'appointmentsToday',
            'activitiesCount',
            'loyaltyAccountsCount',
            'totalCustomersCount',
            'trendLabels',
            'trendSeries',
            'statusLabels',
            'statusSeries',
            'chartPalette',
        ));
    }
}

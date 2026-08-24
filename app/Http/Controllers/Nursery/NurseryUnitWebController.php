<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\Unit;
use App\Services\Nursery\NurseryDashboardService;
use App\Services\Nursery\NurseryUnitService;
use App\Support\NurseryAccess;
use App\Support\NurseryClassroomAgeGroups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class NurseryUnitWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $tab = $request->query('tab', 'active') === 'archived' ? 'archived' : 'active';
        $q = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'newest') === 'oldest' ? 'oldest' : 'newest';

        $base = Unit::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'archived' => (clone $base)->where('is_active', false)->count(),
        ];

        $items = Unit::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', $tab === 'active')
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->when($sort === 'oldest', fn ($q) => $q->orderBy('created_at'), fn ($q) => $q->orderByDesc('created_at'))
            ->paginate(20)
            ->withQueryString();

        $canManage = app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_UNITS);

        return view('nursery.units.index', [
            'items' => $items,
            'listStats' => $listStats,
            'spark' => app(NurseryDashboardService::class)->listSparkMeta($listStats),
            'q' => $q,
            'tab' => $tab,
            'sort' => $sort,
            'canManage' => $canManage,
        ]);
    }

    public function create(): View
    {
        return view('nursery.units.create', $this->formViewData());
    }

    public function store(Request $request, NurseryUnitService $units): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $this->validatePayload($request);

        try {
            $unit = $units->create($tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->input('submit_action') === 'save_and_details') {
            return redirect()
                ->route('nursery.units.edit', $unit)
                ->with('success', 'تم حفظ الوحدة. يمكنك إكمال التفاصيل.');
        }

        if ($request->input('submit_action') === 'save_and_new') {
            return redirect()
                ->route('nursery.units.create')
                ->with('success', 'تم حفظ الوحدة.');
        }

        return redirect()
            ->route('nursery.units.index')
            ->with('success', 'تم إنشاء الوحدة.');
    }

    public function edit(Unit $unit): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $unit->user_id === $tenantUserId, 404);

        return view('nursery.units.edit', array_merge($this->formViewData(), [
            'unit' => $unit->load('activeLessons'),
        ]));
    }

    public function update(Request $request, Unit $unit, NurseryUnitService $units): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $unit->user_id === $tenantUserId, 404);

        $data = $this->validatePayload($request, true);

        try {
            $units->update($unit, $tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.units.index', ['tab' => $unit->is_active ? 'active' : 'archived'])
            ->with('success', 'تم تحديث الوحدة.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formViewData(): array
    {
        return [
            'ageGroupLabels' => NurseryClassroomAgeGroups::labels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $includeStatus = false): array
    {
        $ageKeys = array_keys(NurseryClassroomAgeGroups::labels());

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'age_groups' => ['required', 'array', 'min:1'],
            'age_groups.*' => ['string', Rule::in($ageKeys)],
            'goals' => ['required', 'array', 'min:1'],
            'goals.*' => ['nullable', 'string', 'max:500'],
            'lessons' => ['nullable', 'array'],
            'lessons.*' => ['nullable', 'string', 'max:200'],
        ];

        if ($includeStatus) {
            $rules['is_active'] = ['required', 'string', 'in:active,inactive'];
        }

        return $request->validate($rules);
    }
}

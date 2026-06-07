<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\Classroom;
use App\Services\Nursery\NurseryClassroomService;
use App\Support\NurseryClassroomAgeGroups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class NurseryClassroomWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $base = Classroom::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'archived' => (clone $base)->where('is_active', false)->count(),
        ];

        $items = Classroom::query()
            ->withCount(['enrollments as active_children_count' => fn ($q) => $q->where('is_active', true)])
            ->where('user_id', $tenantUserId)
            ->orderBy('name')
            ->get();

        $canManage = app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CLASSROOMS);

        return view('nursery.classrooms.index', compact('items', 'listStats', 'canManage'));
    }

    public function create(): View
    {
        return view('nursery.classrooms.create', $this->formViewData());
    }

    public function store(Request $request, NurseryClassroomService $classrooms): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $this->validateClassroomPayload($request);

        try {
            $classrooms->create($tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->input('submit_action') === 'save_and_new') {
            return redirect()
                ->route('nursery.classrooms.create')
                ->with('success', 'تم إنشاء الفصل. يمكنك إضافة فصل آخر.');
        }

        return redirect()
            ->route('nursery.classrooms.index')
            ->with('success', 'تم إنشاء الفصل.');
    }

    public function edit(Classroom $classroom): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $classroom->user_id === $tenantUserId, 404);

        return view('nursery.classrooms.edit', array_merge(
            $this->formViewData(),
            ['classroom' => $classroom]
        ));
    }

    public function update(Request $request, Classroom $classroom, NurseryClassroomService $classrooms): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $classroom->user_id === $tenantUserId, 404);

        $data = $this->validateClassroomPayload($request, true);

        try {
            $classrooms->update($classroom, $tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.classrooms.index')
            ->with('success', 'تم تحديث بيانات الفصل.');
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
    private function validateClassroomPayload(Request $request, bool $includeStatus = false): array
    {
        $ageKeys = NurseryClassroomAgeGroups::keys();

        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'age_groups' => ['required', 'array', 'min:1'],
            'age_groups.*' => ['string', Rule::in($ageKeys)],
        ];

        if ($includeStatus) {
            $rules['is_active'] = ['required', 'string', 'in:active,inactive'];
        }

        return $request->validate($rules);
    }
}

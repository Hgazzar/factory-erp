<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\Classroom;
use App\Services\Nursery\DuplicateChildNameException;
use App\Services\Nursery\NurseryChildDailyActivityService;
use App\Services\Nursery\NurseryChildService;
use App\Services\Nursery\NurseryDashboardService;
use App\Services\Nursery\NurseryPortalInviteService;
use App\Support\SaudiRegions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use InvalidArgumentException;

final class NurseryChildWebController extends Controller
{
    use PersistsMorphAttachments;
    use ResolvesOperationsTenant;

    /** @return list<array{value: string, label: string}> */
    private function genderOptions(): array
    {
        return [
            ['value' => 'male', 'label' => 'ذكر'],
            ['value' => 'female', 'label' => 'أنثى'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function relationshipOptions(): array
    {
        return [
            ['value' => 'father', 'label' => 'أب'],
            ['value' => 'mother', 'label' => 'أم'],
            ['value' => 'guardian', 'label' => 'ولي أمر'],
            ['value' => 'other', 'label' => 'أخرى'],
        ];
    }

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $q = trim((string) $request->query('q', ''));
        $classroomId = (int) $request->query('classroom_id', 0);

        $base = Child::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', Child::STATUS_ACTIVE)->count(),
            'archived' => (clone $base)->where('status', Child::STATUS_INACTIVE)->count(),
        ];

        $children = Child::query()
            ->with(['guardian:id,name,phone', 'activeEnrollment.classroom:id,name', 'attachments'])
            ->where('user_id', $tenantUserId)
            ->when($classroomId > 0, function ($query) use ($classroomId): void {
                $query->whereHas('activeEnrollment', fn ($e) => $e
                    ->where('classroom_id', $classroomId)
                    ->where('is_active', true));
            })
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('code', 'like', '%'.$q.'%')
                        ->orWhereHas('guardian', fn ($g) => $g->where('name', 'like', '%'.$q.'%')
                            ->orWhere('phone', 'like', '%'.$q.'%'));
                });
            })
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $classrooms = Classroom::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('nursery.children.index', [
            'children' => $children,
            'q' => $q,
            'listStats' => $listStats,
            'classrooms' => $classrooms,
            'classroomId' => $classroomId,
            'spark' => app(NurseryDashboardService::class)->listSparkMeta($listStats),
        ]);
    }

    public function create(): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        return view('nursery.children.create', $this->formViewData($tenantUserId));
    }

    public function store(Request $request, NurseryChildService $children): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $this->validateChildPayload($request);

        try {
            $child = $children->register($tenantUserId, $data);
        } catch (DuplicateChildNameException $e) {
            // غالباً الحفظ الأول نجح ثم فشل التحويل/الرفع — افتح السجل الموجود بدل رسالة مربكة.
            return redirect()
                ->route('nursery.children.show', $e->existingChild())
                ->with('success', 'هذا الطفل مسجّل مسبقاً لنفس ولي الأمر — تم فتح ملفه.');
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        try {
            $uploads = $request->file('attachments', []) ?? [];
            if (! is_array($uploads)) {
                $uploads = [$uploads];
            }
            if ($uploads !== []) {
                $this->persistMorphAttachments($child, $uploads, $tenantUserId, 'nursery/children');
            }
            $this->persistAvatarUpload(
                $child,
                $request->file('avatar'),
                $tenantUserId,
                'nursery/children',
                $request->boolean('remove_avatar')
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('nursery.children.show', $child)
                ->with('warning', 'تم تسجيل الطفل، لكن تعذّر رفع الصورة/المرفقات. أعد رفعها من «تعديل البيانات».');
        }

        if ($request->boolean('send_portal_invite')) {
            try {
                $invite = app(NurseryPortalInviteService::class)->sendInvite($tenantUserId, $child);
                if ($invite['sent']) {
                    return redirect()
                        ->route('nursery.children.show', $child)
                        ->with('success', 'تم تسجيل الطفل و'.$invite['message']);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($request->input('submit_action') === 'save_and_new') {
            return redirect()
                ->route('nursery.children.create')
                ->with('success', 'تم تسجيل الطفل. يمكنك إضافة طفل آخر.');
        }

        return redirect()
            ->route('nursery.children.show', $child)
            ->with('success', 'تم تسجيل الطفل بنجاح.');
    }

    public function show(Request $request, Child $child, NurseryChildDailyActivityService $dailyActivities): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        $child->load(['guardian', 'activeEnrollment.classroom', 'activeEnrollment.classroom.teacher:id,name', 'attachments', 'medications']);

        $relationshipLabels = collect($this->relationshipOptions())->pluck('label', 'value');

        $recentAttendance = AttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->where('child_id', $child->id)
            ->orderByDesc('attendance_date')
            ->limit(14)
            ->get();

        $activityDate = trim((string) $request->query('date', now()->toDateString()));
        if ($activityDate === '' || strtotime($activityDate) === false) {
            $activityDate = now()->toDateString();
        }
        if ($activityDate > now()->toDateString()) {
            $activityDate = now()->toDateString();
        }

        try {
            $todaysActivities = $dailyActivities->forChildOnDate($tenantUserId, (int) $child->id, $activityDate);
            $dailySummary = $dailyActivities->summary($todaysActivities);
        } catch (\Throwable $e) {
            report($e);
            $todaysActivities = collect();
            $dailySummary = [];
        }

        $canEdit = app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILDREN);
        $canManageChildAttendance = app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILD_ATTENDANCE);
        $canManageChildActivity = app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CHILD_ACTIVITY);
        $portalInviteUrl = null;
        if ($child->guardian !== null) {
            try {
                $portalInviteUrl = app(NurseryPortalInviteService::class)->inviteUrl($tenantUserId, $child->guardian);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('nursery.children.show', compact(
            'child',
            'recentAttendance',
            'relationshipLabels',
            'canEdit',
            'canManageChildAttendance',
            'canManageChildActivity',
            'portalInviteUrl',
            'activityDate',
            'todaysActivities',
            'dailySummary',
        ));
    }

    public function edit(Child $child): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        $child->load(['guardian', 'activeEnrollment.classroom', 'attachments', 'medications']);

        return view('nursery.children.edit', array_merge(
            $this->formViewData($tenantUserId),
            ['child' => $child]
        ));
    }

    public function update(Request $request, Child $child, NurseryChildService $children): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        $data = $this->validateChildPayload($request, true);

        try {
            $child = $children->update($child, $tenantUserId, $data);
        } catch (DuplicateChildNameException $e) {
            return back()->withInput()->with(
                'error',
                'يوجد طفل آخر لنفس ولي الأمر بهذا الاسم ('.$e->existingChild()->code.'). ميّز الاسم.'
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        try {
            $uploads = $request->file('attachments', []) ?? [];
            if (! is_array($uploads)) {
                $uploads = [$uploads];
            }
            if ($uploads !== []) {
                $this->persistMorphAttachments($child, $uploads, $tenantUserId, 'nursery/children');
            }
            $this->persistAvatarUpload(
                $child,
                $request->file('avatar'),
                $tenantUserId,
                'nursery/children',
                $request->boolean('remove_avatar')
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('nursery.children.show', $child)
                ->with('warning', 'تم تحديث البيانات، لكن تعذّر رفع الصورة/المرفقات. أعد المحاولة من التعديل.');
        }

        return redirect()
            ->route('nursery.children.show', $child)
            ->with('success', 'تم تحديث بيانات الطفل.');
    }

    public function archive(Child $child, NurseryChildService $children): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        try {
            $children->archive($child, $tenantUserId);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم أرشفة حساب الطفل.');
    }

    public function restore(Child $child, NurseryChildService $children): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        try {
            $children->restore($child, $tenantUserId);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم إعادة تفعيل حساب الطفل.');
    }

    public function sendPortalInvite(Child $child, NurseryPortalInviteService $inviteService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        $result = $inviteService->sendInvite($tenantUserId, $child);

        return back()->with($result['sent'] ? 'success' : 'error', $result['message']);
    }

    public function citySelectPartial(Request $request): View
    {
        return view('nursery.partials.city-select', [
            'regionKey' => (string) $request->query('region', ''),
            'cityValue' => (string) $request->query('city', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formViewData(int $tenantUserId): array
    {
        return [
            'classrooms' => Classroom::query()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'genderOptions' => $this->genderOptions(),
            'relationshipOptions' => $this->relationshipOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateChildPayload(Request $request, bool $includeStatus = false): array
    {
        $regionKeys = array_keys(SaudiRegions::regions());

        $request->merge([
            'classroom_id' => $request->filled('classroom_id') ? $request->input('classroom_id') : null,
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'classroom_id' => ['nullable', 'integer', 'exists:nursery_classrooms,id'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'diseases' => ['nullable', 'string', 'max:2000'],
            'health_notes' => ['nullable', 'string', 'max:5000'],
            'guardian_name' => ['required', 'string', 'max:120'],
            'guardian_phone' => ['required', 'string', 'max:32'],
            'guardian_relationship' => ['nullable', 'string', 'in:father,mother,guardian,other'],
            'guardian_national_id' => ['nullable', 'string', 'max:64'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_address' => ['nullable', 'string', 'max:500'],
            'guardian_region' => ['nullable', 'string', Rule::in($regionKeys)],
            'guardian_city' => ['nullable', 'string', 'max:120'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
            'medications' => ['nullable', 'array'],
            'medications.*.name' => ['nullable', 'string', 'max:120'],
            'medications.*.dosage' => ['nullable', 'string', 'max:64'],
            'medications.*.frequency' => ['nullable', 'string', 'max:32'],
            'medications.*.schedule_notes' => ['nullable', 'string', 'max:120'],
            'medications.*.notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($includeStatus) {
            $rules['status'] = ['required', 'string', 'in:active,inactive'];
        }

        $data = $request->validate($rules);

        if (($data['classroom_id'] ?? null) === '' || ($data['classroom_id'] ?? null) === null) {
            $data['classroom_id'] = null;
        } else {
            $data['classroom_id'] = (int) $data['classroom_id'];
        }

        $validator = \Validator::make($data, []);
        $validator->after(function (Validator $v) use ($data): void {
            $region = (string) ($data['guardian_region'] ?? '');
            $city = trim((string) ($data['guardian_city'] ?? ''));
            if ($region !== '' && $city !== '') {
                $allowed = SaudiRegions::regions()[$region]['cities'] ?? [];
                if (! in_array($city, $allowed, true)) {
                    $v->errors()->add('guardian_city', 'المدينة لا تتبع المنطقة المختارة.');
                }
            }
        });
        $validator->validate();

        return $data;
    }
}

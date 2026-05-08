<?php

namespace App\Http\Controllers;

use App\Models\CrmSegment;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmSegmentWebController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->id;
        $q = trim((string) $request->string('q', ''));
        $type = trim((string) $request->string('type', ''));
        $status = trim((string) $request->string('status', ''));

        $query = CrmSegment::query()
            ->where('user_id', $tenantId)
            ->withCount('customers');

        if ($q !== '') {
            $query->where(function ($w) use ($q): void {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        if ($type !== '' && array_key_exists($type, CrmSegment::typeLabels())) {
            $query->where('type', $type);
        }

        if ($status !== '' && array_key_exists($status, CrmSegment::statusLabels())) {
            $query->where('status', $status);
        }

        $segments = $query
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('crm.segments.index', [
            'segments' => $segments,
            'segmentTypeOptions' => $this->segmentTypeOptions(),
            'segmentStatusOptions' => $this->segmentStatusOptions(),
        ]);
    }

    public function create(): View
    {
        $tenantId = (int) auth()->id();

        return view('crm.segments.create', [
            'nextSegmentCode' => $this->nextCodeForTenant($tenantId),
            'segmentTypeOptions' => $this->segmentTypeOptions(),
            'segmentStatusOptions' => $this->segmentStatusOptions(),
            'crmStatusOptions' => $this->crmStatusOptions(),
            'sourceOptions' => $this->sourceOptions(),
            'segmentColorOptions' => $this->segmentColorOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;

        $crmStatuses = array_keys($this->crmStatusOptions());
        $segmentTypes = array_keys(CrmSegment::typeLabels());
        $segmentStatuses = array_keys(CrmSegment::statusLabels());
        $sourceList = array_keys($this->sourceOptions());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::in($segmentTypes)],
            'status' => ['required', Rule::in($segmentStatuses)],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'crm_status' => ['nullable', Rule::in($crmStatuses)],
            'source' => ['nullable', Rule::in($sourceList)],
            'region' => ['nullable', 'string', 'max:100'],
            'rating_min' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rating_max' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $criteria = [
            'crm_status' => $data['crm_status'] ?? null,
            'source' => $data['source'] ?? null,
            'region' => $data['region'] ?? null,
            'rating_min' => $data['rating_min'] ?? null,
            'rating_max' => $data['rating_max'] ?? null,
        ];

        if (
            isset($criteria['rating_min'], $criteria['rating_max']) &&
            $criteria['rating_min'] !== null &&
            $criteria['rating_max'] !== null &&
            (int) $criteria['rating_min'] > (int) $criteria['rating_max']
        ) {
            return back()->withInput()->withErrors([
                'rating_max' => 'قيمة "التقييم إلى" يجب أن تكون أكبر أو تساوي "التقييم من".',
            ]);
        }

        $segment = null;
        $membersCount = 0;

        DB::transaction(function () use ($tenantId, $data, $criteria, &$segment, &$membersCount): void {
            $segment = CrmSegment::create([
                'user_id' => $tenantId,
                'code' => $this->nextCodeForTenant($tenantId),
                'name' => $data['name'],
                'type' => $data['type'],
                'status' => $data['status'],
                'color' => strtoupper((string) $data['color']),
                'criteria' => $criteria,
                'last_refreshed_at' => now(),
            ]);

            $membersCount = $this->syncSegmentMembers($segment);
        });

        return redirect()
            ->route('crm.segments.index')
            ->with('success', "تم إنشاء الشريحة بنجاح بعدد أعضاء {$membersCount}.");
    }

    public function refreshMembers(Request $request, CrmSegment $segment): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;
        if ((int) $segment->user_id !== $tenantId) {
            abort(403);
        }

        $membersCount = 0;
        DB::transaction(function () use ($segment, &$membersCount): void {
            $membersCount = $this->syncSegmentMembers($segment);
        });

        return redirect()
            ->route('crm.segments.index')
            ->with('success', "تم إعادة تحديث أعضاء الشريحة بنجاح (تم العثور على {$membersCount} عضو).");
    }

    private function nextCodeForTenant(int $tenantId): string
    {
        $codes = CrmSegment::query()
            ->where('user_id', $tenantId)
            ->where('code', 'like', 'SEG-%')
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^SEG-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = $max + 1;

        return 'SEG-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function buildMembersQuery(int $tenantId, array $criteria)
    {
        $query = Customer::forTenant($tenantId);

        if (! empty($criteria['crm_status'])) {
            $query->where('customers.crm_status', $criteria['crm_status']);
        }
        if (! empty($criteria['source'])) {
            $query->where('customers.source', $criteria['source']);
        }
        if (! empty($criteria['region'])) {
            $query->where('customers.region', 'like', '%'.$criteria['region'].'%');
        }
        if (($criteria['rating_min'] ?? null) !== null) {
            $query->where('customers.lead_rating', '>=', (int) $criteria['rating_min']);
        }
        if (($criteria['rating_max'] ?? null) !== null) {
            $query->where('customers.lead_rating', '<=', (int) $criteria['rating_max']);
        }

        return $query;
    }

    private function syncSegmentMembers(CrmSegment $segment): int
    {
        $criteria = is_array($segment->criteria) ? $segment->criteria : [];
        $memberIds = $this->buildMembersQuery((int) $segment->user_id, $criteria)
            ->pluck('customers.id')
            ->all();

        $segment->customers()->sync($memberIds);
        $segment->forceFill(['last_refreshed_at' => now()])->save();

        return count($memberIds);
    }

    /**
     * @return array<string, string>
     */
    private function crmStatusOptions(): array
    {
        return [
            'potential' => 'محتمل',
            'interested' => 'مهتم',
            'active' => 'نشط',
            'not_interested' => 'غير مهتم',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sourceOptions(): array
    {
        return collect(config('crm_lead_form.sources', []))
            ->mapWithKeys(fn ($row) => [($row['value'] ?? '') => ($row['label'] ?? ($row['value'] ?? ''))])
            ->filter(fn ($label, $value) => $value !== '')
            ->all();
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function segmentTypeOptions(): array
    {
        return collect(CrmSegment::typeLabels())
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function segmentStatusOptions(): array
    {
        return collect(CrmSegment::statusLabels())
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function segmentColorOptions(): array
    {
        return [
            '#2563EB', '#16A34A', '#06B6D4', '#EC4899', '#A855F7',
            '#F97316', '#EAB308', '#10B981', '#EF4444', '#0EA5E9',
        ];
    }
}

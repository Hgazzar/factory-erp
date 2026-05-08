<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
        <h2 class="text-base font-semibold text-gray-900 mb-4"><span class="inline-flex items-center gap-1">معلومات أساسية <x-info field="sales.customers_basic_block" /></span></h2>
        <dl class="space-y-3 text-sm">
            @if($customer->lead_number)
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">رقم العميل المحتمل <x-info field="crm.lead_number_column" /></span></dt>
                    <dd class="text-gray-900 font-mono text-sm font-medium text-left tabular-nums">{{ $customer->lead_number }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">المصدر <x-info field="crm.crm_source" /></span></dt>
                <dd class="text-gray-900 text-left">{{ \App\Models\Customer::labelForLeadSource($customer->source) }}</dd>
            </div>
            @if(filled($customer->source_details))
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">تفاصيل المصدر <x-info field="crm.lead_form_source_details" /></span></dt>
                    <dd class="text-gray-900 text-left max-w-[60%]">{{ $customer->source_details }}</dd>
                </div>
            @endif
            @if(filled($customer->lead_sector))
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">القطاع <x-info field="crm.lead_form_sector" /></span></dt>
                    <dd class="text-gray-900 text-left">{{ \App\Models\Customer::labelForLeadSector($customer->lead_sector) }}</dd>
                </div>
            @endif
            @if(filled($customer->lead_company_size))
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">حجم الشركة <x-info field="crm.lead_form_company_size" /></span></dt>
                    <dd class="text-gray-900 text-left">{{ \App\Models\Customer::labelForLeadCompanySize($customer->lead_company_size) }}</dd>
                </div>
            @endif
            @if($customer->lead_budget !== null)
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">الميزانية <x-info field="crm.lead_form_budget" /></span></dt>
                    <dd class="text-gray-900 text-left tabular-nums">{{ number_format((float) $customer->lead_budget, 2) }}</dd>
                </div>
            @endif
            @if(filled($customer->lead_description))
                <div class="flex flex-col gap-1 border-b border-gray-100 pb-2 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">الوصف <x-info field="crm.lead_form_description" /></span></dt>
                    <dd class="text-gray-900 text-left whitespace-pre-wrap flex-1 min-w-0">{{ $customer->lead_description }}</dd>
                </div>
            @endif
            @if(filled($customer->lead_requirements))
                <div class="flex flex-col gap-1 border-b border-gray-100 pb-2 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">المتطلبات <x-info field="crm.lead_form_requirements" /></span></dt>
                    <dd class="text-gray-900 text-left whitespace-pre-wrap flex-1 min-w-0">{{ $customer->lead_requirements }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">مسؤول المتابعة (CRM) <x-info field="crm.assignee" /></span></dt>
                <dd class="text-gray-900 text-left">
                    @if($customer->assignedUser)
                        @php
                            $parts = preg_split('/\s+/u', trim($customer->assignedUser->name), -1, PREG_SPLIT_NO_EMPTY);
                            $crmIni = '';
                            foreach (array_slice($parts, 0, 2) as $p) {
                                $crmIni .= mb_substr($p, 0, 1);
                            }
                            $crmIni = mb_strtoupper($crmIni);
                        @endphp
                        <span class="inline-flex items-center gap-2 justify-end">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-800" title="{{ $customer->assignedUser->name }}">{{ $crmIni }}</span>
                            <span>{{ $customer->assignedUser->name }}</span>
                        </span>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">الحالة التسويقية <x-info field="crm.crm_status" /></span></dt>
                <dd class="text-left">
                    @php
                        $crmSt = $customer->crm_status ?? 'potential';
                        [$crmLab, $crmBg] = match ($crmSt) {
                            'interested' => ['مهتم', 'bg-sky-50 text-sky-800 border border-sky-100'],
                            'active' => ['نشط', 'bg-emerald-50 text-emerald-800 border border-emerald-100'],
                            'not_interested' => ['غير مهتم', 'bg-red-50 text-red-800 border border-red-100'],
                            default => ['محتمل', 'bg-violet-50 text-violet-900 border border-violet-100'],
                        };
                    @endphp
                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium {{ $crmBg }}">{{ $crmLab }}</span>
                </dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">الأولوية / التقييم <x-info field="crm.lead_priority_field" /></span></dt>
                <dd class="text-left flex flex-wrap items-center gap-3 justify-end">
                    @php
                        [$prLab, $prBg] = match ($customer->lead_priority) {
                            'high' => ['عالية', 'bg-rose-50 text-rose-800 border border-rose-100'],
                            'medium' => ['متوسطة', 'bg-amber-50 text-amber-900 border border-amber-100'],
                            'low' => ['منخفضة', 'bg-teal-50 text-teal-800 border border-teal-100'],
                            default => ['—', 'bg-gray-50 text-gray-500 border border-gray-100'],
                        };
                        $lr = (int) ($customer->lead_rating ?? 0);
                    @endphp
                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium {{ $prBg }}">{{ $prLab }}</span>
                    <span class="inline-flex items-center gap-0.5 text-amber-400" role="img" aria-label="التقييم {{ $lr }} من 5">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $lr)
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" class="shrink-0" aria-hidden="true"><path fill="currentColor" d="M8 .5l2.2 4.46 4.93.72-3.57 3.48.84 4.91L8 11.77l-4.4 2.3.84-4.9L.87 5.68l4.93-.72z"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" class="shrink-0 text-gray-200" aria-hidden="true"><path fill="currentColor" d="M8 .5l2.2 4.46 4.93.72-3.57 3.48.84 4.91L8 11.77l-4.4 2.3.84-4.9L.87 5.68l4.93-.72z"/></svg>
                            @endif
                        @endfor
                    </span>
                </dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">تاريخ إنشاء السجل <x-info field="crm.leads_created_column" /></span></dt>
                <dd class="text-gray-900 text-left tabular-nums">{{ $customer->created_at ? $customer->created_at->timezone(config('app.timezone'))->format('Y/m/d H:i') : '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">رقم ضريبي (VAT) <x-info field="sales.customer_vat_number" /></span></dt>
                <dd class="text-gray-900 font-medium text-left">{{ $customer->vat_number ?? $customer->tax_number ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">البريد <x-info field="crm.leads_email_column" /></span></dt>
                <dd class="text-gray-900 text-left">{{ $customer->email ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">الهاتف</dt>
                <dd class="text-gray-900 text-left">{{ $customer->phone ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">الجوال</dt>
                <dd class="text-gray-900 text-left">{{ $customer->mobile ?? '—' }}</dd>
            </div>
            @if(filled($customer->company_name))
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">الشركة <x-info field="crm.lead_form_company" /></span></dt>
                    <dd class="text-gray-900 text-left">{{ $customer->company_name }}</dd>
                </div>
            @endif
            @if(filled($customer->job_title))
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">المسمى الوظيفي <x-info field="crm.lead_form_job_title" /></span></dt>
                    <dd class="text-gray-900 text-left">{{ $customer->job_title }}</dd>
                </div>
            @endif
            @if(filled($customer->website))
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">الموقع الإلكتروني <x-info field="crm.lead_form_website" /></span></dt>
                    <dd class="text-gray-900 text-left break-all" dir="ltr"><a href="{{ \Illuminate\Support\Str::startsWith($customer->website, ['http://', 'https://']) ? $customer->website : 'https://'.$customer->website }}" class="text-indigo-600 hover:underline" target="_blank" rel="noopener noreferrer">{{ $customer->website }}</a></dd>
                </div>
            @endif
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500"><span class="inline-flex items-center gap-1">الحد الائتماني <x-info field="credit_limit" /></span></dt>
                <dd class="text-gray-900 font-medium text-left">{{ $customer->credit_limit !== null ? 'SAR '.number_format((float) $customer->credit_limit, 2) : '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 pb-1">
                <dt class="text-gray-500"><span class="inline-flex items-center gap-1">أيام السداد <x-info field="sales.customer_payment_terms_days" /></span></dt>
                <dd class="text-gray-900 text-left">{{ $customer->payment_terms_days !== null ? $customer->payment_terms_days.' يوم' : '—' }}</dd>
            </div>
        </dl>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
        <h2 class="text-base font-semibold text-gray-900 mb-4">العنوان والحالة</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">العنوان</dt>
                <dd class="text-gray-900 text-left">{{ $customer->address ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">الدولة / المدينة</dt>
                <dd class="text-gray-900 text-left">{{ trim(implode(' / ', array_filter([$customer->country, $customer->city]))) ?: '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 pb-1">
                <dt class="text-gray-500">الحالة</dt>
                <dd class="text-left">
                    @if(($customer->status ?? '') === 'active' || $customer->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">غير نشط</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>
</div>

<div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-5">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <h2 class="text-base font-semibold text-gray-900">
            <span class="inline-flex items-center gap-1">سجل المتابعات <x-info field="crm.follow_up_timeline" /></span>
        </h2>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 border-0"
                    data-bs-toggle="modal"
                    data-bs-target="#crmQuickAppointmentModal"
                    data-appt-url="{{ route('crm.customers.actions.appointment', $customer) }}"
                    data-customer-label="{{ $customer->display_name }}">
                إضافة موعد
            </button>
            <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium border border-gray-300 text-gray-800 hover:bg-gray-50 bg-white"
                    data-bs-toggle="modal"
                    data-bs-target="#crmQuickCallModal"
                    data-call-url="{{ route('crm.customers.actions.call', $customer) }}"
                    data-customer-label="{{ $customer->display_name }}">
                تسجيل مكالمة
            </button>
        </div>
    </div>
    <div class="space-y-4">
        @forelse($customer->crmActivities as $activity)
            @php
                $ts = $activity->created_at?->timezone(config('app.timezone'))->format('Y/m/d H:i');
                $typeLabel = \App\Models\CrmActivity::labelForType($activity->type);
            @endphp
            <div class="rounded-lg border border-gray-100 bg-gray-50/60 p-4 text-sm">
                <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-semibold bg-white border border-gray-200 text-gray-800">{{ $typeLabel }}</span>
                    <span class="text-sm text-gray-500 tabular-nums">{{ $ts }}</span>
                </div>
                @if($activity->note)
                    <p class="text-gray-800 whitespace-pre-wrap leading-relaxed mb-2">{{ $activity->note }}</p>
                @endif
                @if($activity->result)
                    <p class="text-sm text-gray-600"><span class="font-medium text-gray-700">النتيجة:</span> {{ $activity->result }}</p>
                @endif
                <p class="text-sm text-gray-500 mt-2 mb-0">بواسطة {{ $activity->user?->name ?? '—' }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-500 mb-0">لا توجد متابعات مسجّلة بعد.</p>
        @endforelse
    </div>
</div>

<div id="customer-attachments" class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-5 scroll-mt-24">
    <x-attachment-handler
        hint-field="sales.customer_attachments"
        title="المرفقات"
        :existing="$customer->attachments"
        :uploadable="false"
        :allow-delete="true"
    />
</div>

@include('crm.partials.activity-modals')

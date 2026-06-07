<section class="nursery-card p-5 space-y-5">
    <div class="border-b border-orange-100 pb-3">
        <h2 class="text-lg font-bold text-orange-950">مزايا الحضانة <x-info field="nursery.settings_features_intro" /></h2>
        <p class="text-sm text-orange-800/80 mt-1">فعّل أو عطّل المزايا الإضافية لحسابك — تنعكس فوراً على البوابة والإشعارات والمالية.</p>
    </div>

    @if($canManage)
        <form method="post" action="{{ route('nursery.settings.features.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <ul class="space-y-3">
                @foreach($featurePanel['features'] as $feature)
                    <li class="rounded-xl border border-orange-100 p-4 {{ $feature['locked'] ? 'bg-orange-50/40 opacity-80' : 'bg-white' }}">
                        <label class="flex items-start gap-3 {{ $feature['locked'] ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                            <input type="checkbox"
                                   name="features[]"
                                   value="{{ $feature['key'] }}"
                                   class="mt-1 h-4 w-4 rounded border-orange-300 text-orange-600 focus:ring-orange-500"
                                   @checked($feature['enabled'])
                                   @disabled($feature['locked'])>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-orange-950">
                                    {{ $feature['name_ar'] }}
                                    @if(!empty($feature['hint']))
                                        <x-info :field="'nursery.'.$feature['hint']" />
                                    @endif
                                </span>
                                <span class="block text-xs text-orange-800/75 mt-0.5">{{ $feature['description_ar'] }}</span>
                                @if($feature['locked'] && $feature['locked_reason'])
                                    <span class="block text-xs text-amber-800 mt-1">{{ $feature['locked_reason'] }}</span>
                                @endif
                            </span>
                        </label>
                    </li>
                @endforeach
            </ul>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="nursery-btn nursery-btn-primary">حفظ المزايا</button>
            </div>
        </form>
    @else
        <ul class="space-y-3">
            @foreach($featurePanel['features'] as $feature)
                <li class="rounded-xl border border-orange-100 p-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-orange-950">{{ $feature['name_ar'] }}</p>
                        <p class="text-xs text-orange-800/75">{{ $feature['description_ar'] }}</p>
                    </div>
                    @if($feature['enabled'])
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">مفعّل</span>
                    @else
                        <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">معطّل</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>

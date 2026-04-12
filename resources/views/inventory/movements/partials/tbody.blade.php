@forelse($movements as $m)
<tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
    <td class="whitespace-nowrap px-3 py-3 text-gray-800">{{ $m->created_at?->format('Y-m-d H:i') }}</td>
    <td class="px-3 py-3 text-gray-800">
        @php $typeInfo = $types[$m->movement_type] ?? ['label' => $m->movement_type, 'icon' => '']; @endphp
        @if(($typeInfo['icon'] ?? '') === 'in')
            <span class="mov-type-icon text-emerald-600" title="وارد"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/></svg></span>
        @elseif(($typeInfo['icon'] ?? '') === 'out')
            <span class="mov-type-icon text-red-600" title="صادر"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 4a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 10.293V4.5A.5.5 0 0 1 8 4z"/></svg></span>
        @else
            <span class="mov-type-icon text-gray-500" title="جرد"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-1z"/></svg></span>
        @endif
        {{ $typeInfo['label'] }}
    </td>
    <td class="px-3 py-3">
        <span class="font-semibold text-gray-900">{{ $m->item?->code ?? '—' }}</span>
        <span class="block text-xs text-gray-500">{{ $m->item?->name_ar ?? $m->item?->name_en ?? '—' }}</span>
    </td>
    <td class="px-3 py-3 text-gray-800">{{ $m->warehouse?->name_ar ?? $m->warehouse?->code ?? '—' }}</td>
    <td class="px-3 py-3">
        @php $q = (float) $m->quantity; @endphp
        <span class="{{ $q >= 0 ? 'mov-qty-in' : 'mov-qty-out' }}">
            {{ $q >= 0 ? '+' : '' }}{{ number_format($q, 2) }}
        </span>
    </td>
    <td class="px-3 py-3">
        @if($m->reference_url && $m->reference_number)
            <a href="{{ $m->reference_url }}" class="font-medium text-blue-600 hover:underline">{{ $m->reference_number }}</a>
        @else
            <span class="text-gray-400">—</span>
        @endif
    </td>
    <td class="mov-balance-col px-3 py-3 font-semibold tabular-nums text-gray-900" style="display: none;">{{ number_format($m->balance_after ?? 0, 2) }}</td>
</tr>
@empty
<tr>
    <td colspan="7" class="px-3 py-10 text-center text-gray-500">لا توجد حركات مخزون</td>
</tr>
@endforelse

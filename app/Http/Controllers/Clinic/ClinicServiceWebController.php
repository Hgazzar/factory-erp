<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Services\Clinic\ClinicServiceCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClinicServiceWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(ClinicServiceCatalogService $catalog): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $catalog->seedDefaults($tenantUserId);

        return view('clinic.services.index', [
            'services' => $catalog->activeForTenant($tenantUserId),
        ]);
    }

    public function store(Request $request, ClinicServiceCatalogService $catalog): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'vat_inclusive' => ['nullable', 'boolean'],
        ]);

        $catalog->create($this->resolveOperationsTenantUserId(), $data);

        return back()->with('success', 'تمت إضافة الخدمة.');
    }
}

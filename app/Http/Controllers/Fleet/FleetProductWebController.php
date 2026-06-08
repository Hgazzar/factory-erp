<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetProduct;
use App\Services\Fleet\FleetProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetProductWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $q = trim((string) $request->query('q', ''));

        $base = FleetProduct::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];

        $products = FleetProduct::query()
            ->where('user_id', $tenantUserId)
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%');
            }))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('fleet.products.index', compact('products', 'q', 'listStats'));
    }

    public function create(): View
    {
        return view('fleet.products.create');
    }

    public function store(Request $request, FleetProductService $products): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:64'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        try {
            $products->create($this->resolveOperationsTenantUserId(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('fleet.products.index')->with('success', 'تمت إضافة الصنف.');
    }

    public function edit(FleetProduct $product): View
    {
        return view('fleet.products.edit', compact('product'));
    }

    public function update(Request $request, FleetProduct $product, FleetProductService $products): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:64'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        try {
            $products->update($product, $this->resolveOperationsTenantUserId(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('fleet.products.index')->with('success', 'تم تحديث الصنف.');
    }
}

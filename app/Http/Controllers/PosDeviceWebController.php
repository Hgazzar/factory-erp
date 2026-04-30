<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PosDevice;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosDeviceWebController extends Controller
{
    public function index(Request $request): View
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }

        $devices = PosDevice::query()
            ->with('warehouse')
            ->latest('id')
            ->get()
            ->map(function (PosDevice $device): array {
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'status' => $device->status,
                    'warehouse_name' => $device->warehouse?->name_ar ?: $device->warehouse?->name_en ?: '—',
                    'device_token' => $this->tokenForDevice($device),
                ];
            });

        $warehouses = Warehouse::query()
            ->where('user_id', (int) auth()->id())
            ->active()
            ->whereHas('itemWarehouses.item', function ($query): void {
                $query->where('type', Item::TYPE_FINISHED_GOOD);
            })
            ->orderByRaw("COALESCE(NULLIF(name_ar, ''), name_en) asc")
            ->get(['id', 'name_ar', 'name_en', 'code'])
            ->map(fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'name' => $warehouse->name_ar ?: $warehouse->name_en ?: $warehouse->code,
                'code' => $warehouse->code,
            ]);

        return view('pos.devices.index', [
            'devices' => $devices,
            'warehouses' => $warehouses,
            'autoToken' => (string) $request->query('activate_device', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'warehouse_id' => ['required', 'integer'],
        ]);

        $warehouse = Warehouse::query()
            ->where('user_id', (int) auth()->id())
            ->active()
            ->whereKey((int) $validated['warehouse_id'])
            ->whereHas('itemWarehouses.item', function ($query): void {
                $query->where('type', Item::TYPE_FINISHED_GOOD);
            })
            ->first();

        if (! $warehouse) {
            return back()->withErrors([
                'warehouse_id' => 'يجب اختيار مستودع منتج تام مرتبط بحسابك.',
            ])->withInput();
        }

        $device = PosDevice::create([
            'user_id' => (int) auth()->id(),
            'name' => trim((string) $validated['name']),
            'warehouse_id' => (int) $warehouse->id,
            'status' => PosDevice::STATUS_ACTIVE,
            'mac_address' => 'WEB-'.Str::uuid(),
        ]);

        return redirect()
            ->route('pos.devices.index', ['activate_device' => $this->tokenForDevice($device)])
            ->with('success', 'تم إنشاء الجهاز بنجاح، وجارٍ تفعيله لهذا المتصفح.');
    }

    private function tokenForDevice(PosDevice $device): string
    {
        return base64_encode('pos-device:'.$device->id.':'.$device->user_id);
    }
}

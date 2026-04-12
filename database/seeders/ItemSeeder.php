<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Item;
use App\Models\User;
use App\Models\ItemBomComponent;
use App\Models\ItemWarehouse;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * أصناف تجريبية + BOM مبدئي + عميل ومورد للاختبار السريع.
     */
    public function run(): void
    {
        $this->call(UnitSeeder::class);

        $unitM = Unit::query()->where('code', 'M')->firstOrFail();
        $unitKg = Unit::query()->where('code', 'KG')->firstOrFail();
        $unitPcs = Unit::query()->where('code', 'PCS')->firstOrFail();
        $unitHr = Unit::query()->where('code', 'HR')->firstOrFail();

        $demoUserId = (int) (User::query()->orderBy('id')->value('id') ?? 1);

        $warehouse = Warehouse::withoutGlobalScopes()
            ->where('user_id', $demoUserId)
            ->where('code', 'WH-DEMO')
            ->first();
        if (! $warehouse) {
            $warehouse = Warehouse::withoutGlobalScopes()->create([
                'user_id' => $demoUserId,
                'code' => 'WH-DEMO',
                'name_ar' => 'مستودع تجريبي',
                'name_en' => 'Demo warehouse',
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        $rawItems = [
            [
                'code' => 'DEMO-RM-WOOD',
                'name_ar' => 'خشب',
                'name_en' => 'Wood',
                'unit_id' => $unitM->id,
                'type' => Item::TYPE_RAW_MATERIAL,
                'current_stock' => 500,
                'min_stock' => 0,
                'cost' => 25,
                'selling_price' => null,
            ],
            [
                'code' => 'DEMO-RM-STEEL',
                'name_ar' => 'حديد',
                'name_en' => 'Steel / Iron',
                'unit_id' => $unitKg->id,
                'type' => Item::TYPE_RAW_MATERIAL,
                'current_stock' => 500,
                'min_stock' => 0,
                'cost' => 8,
                'selling_price' => null,
            ],
            [
                'code' => 'DEMO-RM-ALU',
                'name_ar' => 'ألومنيوم',
                'name_en' => 'Aluminum',
                'unit_id' => $unitKg->id,
                'type' => Item::TYPE_RAW_MATERIAL,
                'current_stock' => 500,
                'min_stock' => 0,
                'cost' => 18,
                'selling_price' => null,
            ],
        ];

        $finishedItems = [
            [
                'code' => 'DEMO-FG-DOOR',
                'name_ar' => 'باب خشبي',
                'name_en' => 'Wooden door',
                'unit_id' => $unitPcs->id,
                'type' => Item::TYPE_FINISHED_GOOD,
                'current_stock' => 50,
                'min_stock' => 5,
                'cost' => 400,
                'selling_price' => 650,
            ],
            [
                'code' => 'DEMO-FG-WINDOW',
                'name_ar' => 'شباك ألومنيوم',
                'name_en' => 'Aluminum window',
                'unit_id' => $unitPcs->id,
                'type' => Item::TYPE_FINISHED_GOOD,
                'current_stock' => 50,
                'min_stock' => 5,
                'cost' => 350,
                'selling_price' => 550,
            ],
            [
                'code' => 'DEMO-FG-DESK',
                'name_ar' => 'مكتب',
                'name_en' => 'Office desk',
                'unit_id' => $unitPcs->id,
                'type' => Item::TYPE_FINISHED_GOOD,
                'current_stock' => 50,
                'min_stock' => 5,
                'cost' => 800,
                'selling_price' => 1200,
            ],
        ];

        $serviceItems = [
            [
                'code' => 'DEMO-SV-INSTALL',
                'name_ar' => 'خدمة تركيب وصيانة',
                'name_en' => 'Installation & maintenance service',
                'unit_id' => $unitHr->id,
                'type' => Item::TYPE_SERVICE,
                'current_stock' => 0,
                'min_stock' => 0,
                'cost' => 50,
                'selling_price' => 120,
            ],
        ];

        $syncStock = function (Item $item, float $qty) use ($warehouse) {
            if ($qty <= 0) {
                return;
            }
            ItemWarehouse::query()->updateOrCreate(
                [
                    'user_id' => (int) $item->user_id,
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'quantity' => $qty,
                    'reserved_quantity' => 0,
                ]
            );
        };

        foreach (array_merge($rawItems, $finishedItems, $serviceItems) as $row) {
            $item = Item::withoutGlobalScopes()->updateOrCreate(
                ['code' => $row['code'], 'user_id' => $demoUserId],
                array_merge($row, [
                    'user_id' => $demoUserId,
                    'is_active' => true,
                    'description' => null,
                    'barcode' => null,
                ])
            );
            if (in_array($row['type'], [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD], true)) {
                $syncStock($item, (float) $row['current_stock']);
            }
        }

        $door = Item::withoutGlobalScopes()
            ->where('user_id', $demoUserId)
            ->where('code', 'DEMO-FG-DOOR')
            ->first();
        $wood = Item::withoutGlobalScopes()
            ->where('user_id', $demoUserId)
            ->where('code', 'DEMO-RM-WOOD')
            ->first();
        if ($door && $wood) {
            ItemBomComponent::query()->updateOrCreate(
                [
                    'finished_item_id' => $door->id,
                    'component_item_id' => $wood->id,
                ],
                [
                    'quantity_per_unit' => 2,
                ]
            );
        }

        Customer::withoutGlobalScopes()->updateOrCreate(
            ['code' => 'CUST-DEMO', 'user_id' => $demoUserId],
            [
                'name' => 'عميل تجريبي',
                'name_ar' => 'عميل تجريبي',
                'contact_name' => 'جهة اتصال',
                'phone' => '0500000001',
                'email' => 'demo.customer@example.com',
                'address' => 'الرياض',
                'is_active' => true,
                'status' => 'active',
            ]
        );

    }
}

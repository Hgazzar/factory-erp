<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemModule;
use Illuminate\Database\Seeder;

class SystemModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('modules.modules', []) as $key => $meta) {
            SystemModule::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name_ar' => $meta['name_ar'],
                    'name_en' => $meta['name_en'],
                    'description_ar' => $meta['description_ar'] ?? null,
                    'is_core' => (bool) ($meta['is_core'] ?? false),
                    'niche_tags' => $meta['niche_tags'] ?? [],
                    'sort_order' => (int) ($meta['sort_order'] ?? 0),
                ]
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SuperAdmin\SuperAdminTenantService;
use Illuminate\Console\Command;

final class RenameTenantSlugCommand extends Command
{
    protected $signature = 'tenant:rename-slug
                            {identifier : البريد الإلكتروني أو معرّف المستخدم}
                            {slug : الـ slug الجديد (مثل retail-store)}';

    protected $description = 'تغيير slug مستأجر (المتجر العام /s/{slug}) بدون توليد تلقائي';

    public function handle(SuperAdminTenantService $tenants): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $newSlug = trim((string) $this->argument('slug'));

        $user = str_contains($identifier, '@')
            ? User::query()->where('email', $identifier)->first()
            : User::query()->whereKey((int) $identifier)->first();

        if ($user === null) {
            $this->error('لم يُعثر على المستخدم.');

            return self::FAILURE;
        }

        if ($user->role !== 'admin') {
            $this->error('المستخدم ليس مالك مستأجر (admin).');

            return self::FAILURE;
        }

        try {
            $profile = $tenants->updateTenantSlug($user, $newSlug);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $slug = $profile->slug ?? $profile->domain;
        $this->info("تم التحديث: {$user->name} <{$user->email}>");
        $this->line('Slug: '.$slug);
        $this->line('المتجر: '.url('/s/'.$slug));

        return self::SUCCESS;
    }
}

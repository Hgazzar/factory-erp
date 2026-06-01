<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\SuperAdminTenantService;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(SuperAdminTenantService $tenants): View
    {
        return view('super-admin.dashboard.index', [
            'stats' => $tenants->platformStats(),
        ]);
    }
}

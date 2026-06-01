<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Services\Reports\AuditLogViewerService;
use App\Support\AuditModuleCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly AuditLogViewerService $viewer,
    ) {}

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $source = (string) $request->input('source', 'changes');

        $filters = [
            'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : null,
            'action' => $request->filled('action') ? (string) $request->input('action') : null,
            'module' => $request->filled('module') ? (string) $request->input('module') : null,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $users = $this->viewer->filterUsers($tenantUserId);
        $trailModules = AuditModuleCatalog::trailModuleLabels();
        $trailActions = AuditModuleCatalog::trailActionLabels();
        $controlActions = AuditModuleCatalog::controlActionLabels();
        $controlModules = AuditModuleCatalog::controlModuleLabels();

        if ($source === 'control') {
            $logs = $this->viewer->paginateControlLogs($tenantUserId, $filters);
        } else {
            $logs = $this->viewer->paginateTrails($tenantUserId, $filters);
            $source = 'changes';
        }

        return view('system.audit.index', compact(
            'logs',
            'filters',
            'source',
            'users',
            'trailModules',
            'trailActions',
            'controlActions',
            'controlModules',
        ));
    }
}

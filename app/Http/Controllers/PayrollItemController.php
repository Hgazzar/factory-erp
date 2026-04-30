<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Payroll;
use App\Models\PaySlip;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PayrollItemController extends Controller
{
    /**
     * قسيمة راتب لقسيمة محددة داخل دورة الرواتب.
     */
    public function payslip(Payroll $payroll, PaySlip $slip): View
    {
        Gate::authorize('manage_payroll');

        if ((int) $slip->payroll_cycle_id !== (int) $payroll->id) {
            abort(404);
        }

        if (! in_array($payroll->status, [Payroll::STATUS_APPROVED, Payroll::STATUS_PAID], true)) {
            abort(404);
        }

        $slip->load(['employee.department', 'items', 'payrollCycle']);

        $company = CompanySetting::forTenant((int) $payroll->user_id);
        $logoDataUri = null;
        if ($company?->logo_url && str_starts_with((string) $company->logo_url, 'company/')) {
            if (Storage::disk('public')->exists($company->logo_url)) {
                $mime = Storage::disk('public')->mimeType($company->logo_url) ?: 'image/png';
                $bytes = Storage::disk('public')->get($company->logo_url);
                if ($bytes !== null && $bytes !== '') {
                    $logoDataUri = 'data:'.$mime.';base64,'.base64_encode($bytes);
                }
            }
        }

        return view('hr.payrolls.payslip', [
            'payroll' => $payroll,
            'slip' => $slip,
            'company' => $company,
            'logoDataUri' => $logoDataUri,
        ]);
    }
}

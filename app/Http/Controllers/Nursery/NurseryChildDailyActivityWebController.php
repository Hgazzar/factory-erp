<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\Child;
use App\Models\Nursery\ChildDailyActivity;
use App\Models\Nursery\Classroom;
use App\Services\Nursery\NurseryChildDailyActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class NurseryChildDailyActivityWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function store(
        Request $request,
        Child $child,
        NurseryChildDailyActivityService $activities,
    ): RedirectResponse {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        $date = (string) $request->input('activity_date', now()->toDateString());

        try {
            $activities->create(
                $tenantUserId,
                $child,
                $request->all(),
                (int) auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            return $this->afterWriteRedirect($request, $tenantUserId, $child, $date, 'error', $e->getMessage())
                ->withInput();
        }

        return $this->afterWriteRedirect($request, $tenantUserId, $child, $date, 'success', 'تم تسجيل نشاط اليوم.');
    }

    public function update(
        Request $request,
        Child $child,
        ChildDailyActivity $activity,
        NurseryChildDailyActivityService $activities,
    ): RedirectResponse {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);
        abort_unless((int) $activity->child_id === (int) $child->id, 404);

        $date = $activity->activity_date?->toDateString() ?? now()->toDateString();

        try {
            $activities->update($tenantUserId, $activity, $request->all());
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('nursery.children.show', ['child' => $child, 'date' => $date])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.children.show', ['child' => $child, 'date' => $date])
            ->with('success', 'تم تحديث السجل.');
    }

    public function destroy(
        Child $child,
        ChildDailyActivity $activity,
        NurseryChildDailyActivityService $activities,
    ): RedirectResponse {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $child->user_id === $tenantUserId, 404);
        abort_unless((int) $activity->child_id === (int) $child->id, 404);

        $date = $activity->activity_date?->toDateString() ?? now()->toDateString();
        $activities->delete($tenantUserId, $activity);

        return redirect()
            ->route('nursery.children.show', ['child' => $child, 'date' => $date])
            ->with('success', 'تم حذف السجل.');
    }

    private function afterWriteRedirect(
        Request $request,
        int $tenantUserId,
        Child $child,
        string $date,
        string $flashKey,
        string $message,
    ): RedirectResponse {
        if ($request->input('return_to') === 'classroom_today') {
            $classroomId = (int) $request->input('classroom_id');
            $classroom = Classroom::query()
                ->where('user_id', $tenantUserId)
                ->whereKey($classroomId)
                ->first();

            if ($classroom !== null) {
                return redirect()
                    ->route('nursery.classrooms.today', $classroom)
                    ->with($flashKey, $message);
            }
        }

        return redirect()
            ->route('nursery.children.show', ['child' => $child, 'date' => $date])
            ->with($flashKey, $message);
    }
}

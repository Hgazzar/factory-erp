<?php

declare(strict_types=1);

namespace App\Services\Hr;

use Carbon\Carbon;

final readonly class ShiftScheduleContext
{
    public function __construct(
        public ?int $shiftId,
        public Carbon $scheduledStart,
        public Carbon $scheduledEnd,
        public int $graceMinutes,
    ) {}
}

<?php

return [
    'scheduled_start' => env('ATTENDANCE_SCHEDULED_START', '08:00'),
    'grace_minutes' => (int) env('ATTENDANCE_GRACE_MINUTES', 0),
    /** ساعات العمل الافتراضية في اليوم لاحتساب سعر الساعة من الراتب اليومي/الشهري. */
    'hours_per_work_day' => (int) env('ATTENDANCE_HOURS_PER_WORK_DAY', 8),
    /** عدد أيام الراتب الشهري المستخدمة لقسمة الراتب (شهري ÷ 30 ÷ 8 = ساعة). */
    'days_per_month_for_payroll' => (int) env('ATTENDANCE_DAYS_PER_MONTH_PAYROLL', 30),
    /** ساعات العمل الافتراضية في الأسبوع (راتب أسبوعي ÷ 40 = ساعة). */
    'work_hours_per_week' => (int) env('ATTENDANCE_WORK_HOURS_PER_WEEK', 40),
    /** أيام العمل الافتراضية في الأسبوع (تُستخدم ل«يوم بيوم» عند الراتب الأسبوعي). */
    'work_days_per_week' => (int) env('ATTENDANCE_WORK_DAYS_PER_WEEK', 5),
];

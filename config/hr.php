<?php

return [

    /*
    | أرقام أيام الأسبوع حسب ISO-8601: 1=الإثنين … 7=الأحد
    | أيام تُستثنى من «أيام العمل» عند طلب/اعتماد الإجازة (مثال: الجمعة والسبت).
    */
    'leave_excluded_iso_weekdays' => [5, 6],

    /*
    |--------------------------------------------------------------------------
    | احتساب الرواتب (قابل للتوسعة لاحقاً)
    |--------------------------------------------------------------------------
    | insurance_calculation / tax_calculation: fixed_from_employee | percent_of_gross
    */
    'payroll' => [
        'standard_daily_hours' => 8,
        'standard_monthly_days' => 30,
        'overtime_hourly_rate_multiplier' => 1.5,
        'insurance_calculation' => 'fixed_from_employee',
        'insurance_percent' => 0,
        'tax_calculation' => 'fixed_from_employee',
        'tax_percent' => 0,
    ],

];

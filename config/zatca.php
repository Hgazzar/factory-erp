<?php

return [
    /*
    |--------------------------------------------------------------------------
    | مؤقت: سر لاستدعاء مسار HTTP لتوليد CSR على Railway (بدون SSH)
    |--------------------------------------------------------------------------
    | عيّن في Railway: ZATCA_CSR_ROUTE_SECRET=سلسلة_عشوائية_طويلة
    | ثم افتح: /__zatca-generate-csr?token=...  واحذف الراوت بعد الانتهاء.
    */
    'csr_route_secret' => env('ZATCA_CSR_ROUTE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Compliance CSID (Onboarding) — Fatoora
    |--------------------------------------------------------------------------
    | يُستخدم POST مع ترويسة OTP ومحتوى JSON { "csr": "<base64>" }.
    | يمكن تجاوز العناوين عبر المتغيرات البيئية عند تغيير مسارات ZATCA.
    */
    'compliance_urls' => [
        'sandbox' => env(
            'ZATCA_COMPLIANCE_SANDBOX_URL',
            'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation/compliance',
        ),
        'production' => env(
            'ZATCA_COMPLIANCE_PRODUCTION_URL',
            'https://gw-fatoora.zatca.gov.sa/e-invoicing/core/compliance',
        ),
    ],
];

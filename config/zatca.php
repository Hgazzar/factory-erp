<?php

return [
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

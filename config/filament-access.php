<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament /admin panel — allowed user IDs (اختياري)
    |--------------------------------------------------------------------------
    |
    | بالإضافة لهذه القائمة، أي مستخدم دوره admin أو super_admin يُسمح له بالدخول
    | (انظر App\Support\FilamentAccess::userMayAccessPanel).
    |
    | Comma-separated user IDs لمن يصل للوحة من غير الأدمن (مثل مشرف معيّن).
    | الضيوف يتلقون 404؛ يُفضَّل الدخول من التطبيق الرئيسي ثم فتح /admin.
    |
    | Example: FILAMENT_ALLOWED_USER_IDS=1
    |
    */
    'allowed_user_ids' => array_values(array_filter(array_map(
        'intval',
        array_map('trim', explode(',', (string) env('FILAMENT_ALLOWED_USER_IDS', '')))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Logged-in but not allowed
    |--------------------------------------------------------------------------
    |
    | not_found — abort(404) (hides existence, same as guests)
    | redirect   — redirect to the main dashboard route
    |
    */
    'unauthorized_response' => env('FILAMENT_UNAUTHORIZED_RESPONSE', 'not_found'),

];

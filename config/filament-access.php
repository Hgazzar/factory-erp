<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament /admin panel — allowed user IDs
    |--------------------------------------------------------------------------
    |
    | Comma-separated user IDs that may access the Filament panel at /admin.
    | Everyone else receives 404 (guests) or redirect/404 (logged-in), so the
    | panel stays hidden from clients. Log in via the main app first, then
    | open /admin in the browser (no menu link is shown).
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

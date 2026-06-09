<?php

declare(strict_types=1);

return [

    'agent_api' => [
        /** @var list<string> */
        'token_abilities' => ['fleet:agent'],
        'token_expiry_days' => (int) env('FLEET_AGENT_TOKEN_EXPIRY_DAYS', 90),
        'pin_min_length' => 4,
        'pin_max_length' => 8,
    ],

];

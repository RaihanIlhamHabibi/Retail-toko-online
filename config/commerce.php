<?php

return [
    'jwt_secret' => env('JWT_SECRET'),
    'jwt_issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
    'jwt_ttl_minutes' => max(1, min(1440, (int) env('JWT_TTL_MINUTES', 60))),
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
];

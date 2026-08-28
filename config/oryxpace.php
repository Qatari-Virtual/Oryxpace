<?php

return [
    'jwt_secret' => env('AUTH_JWT_SECRET', env('APP_KEY')),
    'jwt_ttl_minutes' => (int) env('AUTH_JWT_TTL_MINUTES', 10080),
    'executive_callsigns' => ['QRV001', 'QRV002', 'QRV003', 'QRV004'],
];

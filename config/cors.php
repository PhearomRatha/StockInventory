<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'https://stock-inventory-front.vercel.app'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'supports_credentials' => true,
];
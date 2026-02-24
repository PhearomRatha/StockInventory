<?php

return [



    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    // 'allowed_origins' => ['https://stockinventoryfront.onrender.com'],
  
    'allowed_origins' => ['https://stock-inventory-front.vercel.app','http://localhost:5173'],

    'allowed_methods' => ['*'],



    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],

    'same_site' => 'none',
    //set to false when deploy

    'supports_credentials' => true,


];

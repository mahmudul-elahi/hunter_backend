<?php

return [
    'api_base_url' => env('REVENUECAT_API_BASE_URL', 'https://api.revenuecat.com/v2'),
    'secret_api_key' => env('REVENUECAT_SECRET_API_KEY'),
    'project_id' => env('REVENUECAT_PROJECT_ID'),
    'webhook_authorization' => env('REVENUECAT_WEBHOOK_AUTHORIZATION'),
    'premium_entitlement_id' => env('REVENUECAT_PREMIUM_ENTITLEMENT_ID', 'premium'),
];

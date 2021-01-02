<?php
/**
 * bunq.php

 */

declare(strict_types=1);

return [
    'version'         => '2.1.1',
    'access_token'    => env('FIREFLY_III_ACCESS_TOKEN', ''),
    'url'             => env('FIREFLY_III_URL', ''),
    'vanity_url'      => envNonEmpty('VANITY_URL'),
    'api_code'        => env('BUNQ_API_CODE', ''),
    'api_url'         => env('BUNQ_API_URL', ''),
    'minimum_version' => '5.4.0',
    'use_sandbox'     => 'https://api.bunq.com' !== env('BUNQ_API_URL', ''),
    'use_production'  => 'https://api.bunq.com' === env('BUNQ_API_URL', ''),
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),
    'connection' => [
        'verify'  => env('VERIFY_TLS_SECURITY', true),
        'timeout' => (float) env('CONNECTION_TIMEOUT', 3.14),
    ],
];

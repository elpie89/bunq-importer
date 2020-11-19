<?php
/**
 * bunq.php

 */

declare(strict_types=1);

return [
    'version'         => '2.0.3',
    'access_token'    => env('FIREFLY_III_ACCESS_TOKEN', ''),
    'uri'             => env('FIREFLY_III_URI', ''),
    'vanity_uri'      => envNonEmpty('VANITY_URI'),
    'api_code'        => env('BUNQ_API_CODE', ''),
    'api_uri'         => env('BUNQ_API_URI', ''),
    'minimum_version' => '5.3.0',
    'use_sandbox'     => 'https://api.bunq.com' !== env('BUNQ_API_URI', ''),
    'use_production'  => 'https://api.bunq.com' === env('BUNQ_API_URI', ''),
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),
    'connection' => [
        'verify'  => env('VERIFY_TLS_SECURITY', true),
        'timeout' => (float) env('CONNECTION_TIMEOUT', 3.14),
    ],
];

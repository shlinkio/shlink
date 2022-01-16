<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Config\env;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

return [

    'not_found_redirects' => [
        'invalid_short_url' => env('DEFAULT_INVALID_SHORT_URL_REDIRECT'),
        'regular_404' => env('DEFAULT_REGULAR_404_REDIRECT'),
        'base_url' => env('DEFAULT_BASE_URL_REDIRECT'),
    ],

    'redirects' => [
        'redirect_status_code' => (int) env('REDIRECT_STATUS_CODE', DEFAULT_REDIRECT_STATUS_CODE),
        'redirect_cache_lifetime' => (int) env('REDIRECT_CACHE_LIFETIME', DEFAULT_REDIRECT_CACHE_LIFETIME),
    ],

];

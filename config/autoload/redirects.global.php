<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

return [

    'not_found_redirects' => [
        'invalid_short_url' => env('DEFAULT_INVALID_SHORT_URL_REDIRECT'),
        'regular_404' => env('DEFAULT_REGULAR_404_REDIRECT'),
        'base_url' => env('DEFAULT_BASE_URL_REDIRECT'),
    ],

    'url_shortener' => [
        // TODO Move these options to their own config namespace. Maybe "redirects".
        'redirect_status_code' => (int) env('REDIRECT_STATUS_CODE', DEFAULT_REDIRECT_STATUS_CODE),
        'redirect_cache_lifetime' => (int) env('REDIRECT_CACHE_LIFETIME', DEFAULT_REDIRECT_CACHE_LIFETIME),
    ],

];

<?php
declare(strict_types=1);

use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use function Shlinkio\Shlink\Common\env;

return [

    'url_shortener' => [
        'domain' => [
            'schema' => env('SHORTENED_URL_SCHEMA', 'http'),
            'hostname' => env('SHORTENED_URL_HOSTNAME'),
        ],
        'shortcode_chars' => env('SHORTCODE_CHARS', UrlShortenerOptions::DEFAULT_CHARS),
        'validate_url' => true,
        'not_found_short_url' => [
            'enable_redirection' => false,
            'redirect_to' => null,
        ],
    ],

];

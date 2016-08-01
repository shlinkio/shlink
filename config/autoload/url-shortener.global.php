<?php
use Shlinkio\Shlink\Core\Service\UrlShortener;

return [

    'url_shortener' => [
        'domain' => [
            'schema' => env('SHORTENED_URL_SCHEMA', 'http'),
            'hostname' => env('SHORTENED_URL_HOSTNAME'),
        ],
        'shortcode_chars' => env('SHORTCODE_CHARS', UrlShortener::DEFAULT_CHARS),
    ],

];

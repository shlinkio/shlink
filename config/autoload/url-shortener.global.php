<?php
use Shlinkio\Shlink\Common;
use Shlinkio\Shlink\Core\Service\UrlShortener;

return [

    'url_shortener' => [
        'domain' => [
            'schema' => Common\env('SHORTENED_URL_SCHEMA', 'http'),
            'hostname' => Common\env('SHORTENED_URL_HOSTNAME'),
        ],
        'shortcode_chars' => Common\env('SHORTCODE_CHARS', UrlShortener::DEFAULT_CHARS),
        'validate_url' => true,
    ],

];

<?php
return [

    'url_shortener' => [
        'domain' => [
            'schema' => getenv('SHORTENED_URL_SCHEMA') ?: 'http',
            'hostname' => getenv('SHORTENED_URL_HOSTNAME'),
        ],
        'shortcode_chars' => getenv('SHORTCODE_CHARS'),
    ],

];

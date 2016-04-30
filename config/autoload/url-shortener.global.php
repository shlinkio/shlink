<?php
return [

    'url-shortener' => [
        'schema' => getenv('SHORTENED_URL_SCHEMA') ?: 'http',
        'hostname' => getenv('SHORTENED_URL_HOSTNAME'),
    ],

];

<?php

declare(strict_types=1);

use const Shlinkio\Shlink\Core\DEFAULT_SHORT_CODES_LENGTH;

return [

    'url_shortener' => [
        'domain' => [
            'schema' => 'https',
            'hostname' => '',
        ],
        'validate_url' => false,
        'obfuscate_remote_addr' => true,
        'visits_webhooks' => [],
        'default_short_codes_length' => DEFAULT_SHORT_CODES_LENGTH,
    ],

];

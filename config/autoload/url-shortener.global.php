<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;
use const Shlinkio\Shlink\MIN_SHORT_CODES_LENGTH;

return (static function (): array {
    $shortCodesLength = max(
        (int) env('DEFAULT_SHORT_CODES_LENGTH', DEFAULT_SHORT_CODES_LENGTH),
        MIN_SHORT_CODES_LENGTH,
    );

    return [

        'url_shortener' => [
            'domain' => [
                'schema' => ((bool) env('IS_HTTPS_ENABLED', true)) ? 'https' : 'http',
                'hostname' => env('DEFAULT_DOMAIN', ''),
            ],
            'default_short_codes_length' => $shortCodesLength,
            'auto_resolve_titles' => (bool) env('AUTO_RESOLVE_TITLES', false),
            'append_extra_path' => (bool) env('REDIRECT_APPEND_EXTRA_PATH', false),
        ],

    ];
})();

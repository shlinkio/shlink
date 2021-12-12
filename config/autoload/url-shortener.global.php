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
    $resolveSchema = static function (): string {
        // Deprecated. For v3, IS_HTTPS_ENABLED should be true by default, instead of null
//        return ((bool) env('IS_HTTPS_ENABLED', true)) ? 'https' : 'http';
        $isHttpsEnabled = env('IS_HTTPS_ENABLED', env('USE_HTTPS'));
        if ($isHttpsEnabled !== null) {
            $boolIsHttpsEnabled = (bool) $isHttpsEnabled;
            return $boolIsHttpsEnabled ? 'https' : 'http';
        }

        return env('SHORT_DOMAIN_SCHEMA', 'http');
    };

    return [

        'url_shortener' => [
            'domain' => [
                // Deprecated SHORT_DOMAIN_* env vars
                'schema' => $resolveSchema(),
                'hostname' => env('DEFAULT_DOMAIN', env('SHORT_DOMAIN_HOST', '')),
            ],
            'validate_url' => (bool) env('VALIDATE_URLS', false), // Deprecated
            'default_short_codes_length' => $shortCodesLength,
            'auto_resolve_titles' => (bool) env('AUTO_RESOLVE_TITLES', false),
            'append_extra_path' => (bool) env('REDIRECT_APPEND_EXTRA_PATH', false),
        ],

    ];
})();

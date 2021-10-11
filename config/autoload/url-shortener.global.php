<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;
use const Shlinkio\Shlink\MIN_SHORT_CODES_LENGTH;

return (static function (): array {
    $shortCodesLength = (int) env('DEFAULT_SHORT_CODES_LENGTH', DEFAULT_SHORT_CODES_LENGTH);
    $shortCodesLength = $shortCodesLength < MIN_SHORT_CODES_LENGTH ? MIN_SHORT_CODES_LENGTH : $shortCodesLength;
    $resolveSchema = static function (): string {
        $useHttps = env('USE_HTTPS'); // Deprecated. For v3, set this to true by default, instead of null
        if ($useHttps !== null) {
            $boolUseHttps = (bool) $useHttps;
            return $boolUseHttps ? 'https' : 'http';
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

<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;
use const Shlinkio\Shlink\MIN_SHORT_CODES_LENGTH;

return (static function (): array {
    $shortCodesLength = max(
        (int) EnvVars::DEFAULT_SHORT_CODES_LENGTH->loadFromEnv(DEFAULT_SHORT_CODES_LENGTH),
        MIN_SHORT_CODES_LENGTH,
    );
    $modeFromEnv = EnvVars::SHORT_URL_MODE->loadFromEnv(ShortUrlMode::STRICT->value);
    $mode = ShortUrlMode::tryDeprecated($modeFromEnv) ?? ShortUrlMode::STRICT;

    return [

        'url_shortener' => [
            'domain' => [ // TODO Refactor this structure to url_shortener.schema and url_shortener.default_domain
                'schema' => ((bool) EnvVars::IS_HTTPS_ENABLED->loadFromEnv(true)) ? 'https' : 'http',
                'hostname' => EnvVars::DEFAULT_DOMAIN->loadFromEnv(''),
            ],
            'default_short_codes_length' => $shortCodesLength,
            'auto_resolve_titles' => (bool) EnvVars::AUTO_RESOLVE_TITLES->loadFromEnv(false),
            'append_extra_path' => (bool) EnvVars::REDIRECT_APPEND_EXTRA_PATH->loadFromEnv(false),
            'multi_segment_slugs_enabled' => (bool) EnvVars::MULTI_SEGMENT_SLUGS_ENABLED->loadFromEnv(false),
            'trailing_slash_enabled' => (bool) EnvVars::SHORT_URL_TRAILING_SLASH->loadFromEnv(false),
            'mode' => $mode,
        ],

    ];
})();

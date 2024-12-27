<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

use function max;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;
use const Shlinkio\Shlink\MIN_SHORT_CODES_LENGTH;

final readonly class UrlShortenerOptions
{
    /**
     * @param 'http'|'https' $schema
     */
    public function __construct(
        public string $defaultDomain = '',
        public string $schema = 'http',
        public int $defaultShortCodesLength = DEFAULT_SHORT_CODES_LENGTH,
        public bool $autoResolveTitles = false,
        public bool $multiSegmentSlugsEnabled = false,
        public bool $trailingSlashEnabled = false,
        public ShortUrlMode $mode = ShortUrlMode::STRICT,
        public ExtraPathMode $extraPathMode = ExtraPathMode::DEFAULT,
    ) {
    }

    public static function fromEnv(): self
    {
        $shortCodesLength = max(
            (int) EnvVars::DEFAULT_SHORT_CODES_LENGTH->loadFromEnv(),
            MIN_SHORT_CODES_LENGTH,
        );

        // Deprecated. Initialize extra path from REDIRECT_APPEND_EXTRA_PATH.
        $appendExtraPath = EnvVars::REDIRECT_APPEND_EXTRA_PATH->loadFromEnv();
        $extraPathMode = $appendExtraPath ? ExtraPathMode::APPEND : ExtraPathMode::DEFAULT;

        // If REDIRECT_EXTRA_PATH_MODE was explicitly provided, it has precedence
        $extraPathModeFromEnv = EnvVars::REDIRECT_EXTRA_PATH_MODE->loadFromEnv();
        if ($extraPathModeFromEnv !== null) {
            $extraPathMode = ExtraPathMode::tryFrom($extraPathModeFromEnv) ?? ExtraPathMode::DEFAULT;
        }

        return new self(
            defaultDomain: EnvVars::DEFAULT_DOMAIN->loadFromEnv(),
            schema: ((bool) EnvVars::IS_HTTPS_ENABLED->loadFromEnv()) ? 'https' : 'http',
            defaultShortCodesLength: $shortCodesLength,
            autoResolveTitles: (bool) EnvVars::AUTO_RESOLVE_TITLES->loadFromEnv(),
            multiSegmentSlugsEnabled: (bool) EnvVars::MULTI_SEGMENT_SLUGS_ENABLED->loadFromEnv(),
            trailingSlashEnabled: (bool) EnvVars::SHORT_URL_TRAILING_SLASH->loadFromEnv(),
            mode: ShortUrlMode::tryFrom(EnvVars::SHORT_URL_MODE->loadFromEnv()) ?? ShortUrlMode::STRICT,
            extraPathMode: $extraPathMode,
        );
    }

    public function isLooseMode(): bool
    {
        return $this->mode === ShortUrlMode::LOOSE;
    }
}

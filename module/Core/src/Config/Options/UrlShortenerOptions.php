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
        public bool $appendExtraPath = false,
        public bool $multiSegmentSlugsEnabled = false,
        public bool $trailingSlashEnabled = false,
        public ShortUrlMode $mode = ShortUrlMode::STRICT,
    ) {
    }

    public static function fromEnv(): self
    {
        $shortCodesLength = max(
            (int) EnvVars::DEFAULT_SHORT_CODES_LENGTH->loadFromEnv(),
            MIN_SHORT_CODES_LENGTH,
        );
        $mode = EnvVars::SHORT_URL_MODE->loadFromEnv();

        return new self(
            defaultDomain: EnvVars::DEFAULT_DOMAIN->loadFromEnv(),
            schema: ((bool) EnvVars::IS_HTTPS_ENABLED->loadFromEnv()) ? 'https' : 'http',
            defaultShortCodesLength: $shortCodesLength,
            autoResolveTitles: (bool) EnvVars::AUTO_RESOLVE_TITLES->loadFromEnv(),
            appendExtraPath: (bool) EnvVars::REDIRECT_APPEND_EXTRA_PATH->loadFromEnv(),
            multiSegmentSlugsEnabled: (bool) EnvVars::MULTI_SEGMENT_SLUGS_ENABLED->loadFromEnv(),
            trailingSlashEnabled: (bool) EnvVars::SHORT_URL_TRAILING_SLASH->loadFromEnv(),
            mode: ShortUrlMode::tryFrom($mode) ?? ShortUrlMode::STRICT,
        );
    }

    public function isLooseMode(): bool
    {
        return $this->mode === ShortUrlMode::LOOSE;
    }
}

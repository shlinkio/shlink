<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

final class UrlShortenerOptions
{
    public function __construct(
        /** @var array{schema: ?string, hostname: ?string} */
        public readonly array $domain = ['schema' => null, 'hostname' => null],
        public readonly int $defaultShortCodesLength = DEFAULT_SHORT_CODES_LENGTH,
        public readonly bool $autoResolveTitles = false,
        public readonly bool $appendExtraPath = false,
        public readonly bool $multiSegmentSlugsEnabled = false,
        public readonly bool $trailingSlashEnabled = false,
        public readonly ShortUrlMode $mode = ShortUrlMode::STRICT,
    ) {
    }

    public function isLooseMode(): bool
    {
        return $this->mode === ShortUrlMode::LOOSE;
    }
}

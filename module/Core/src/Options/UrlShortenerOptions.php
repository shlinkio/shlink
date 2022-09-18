<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

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
    ) {
    }
}

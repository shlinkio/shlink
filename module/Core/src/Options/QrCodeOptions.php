<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ERROR_CORRECTION;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_FORMAT;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_MARGIN;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ROUND_BLOCK_SIZE;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_SIZE;

readonly final class QrCodeOptions
{
    public function __construct(
        public int $size = DEFAULT_QR_CODE_SIZE,
        public int $margin = DEFAULT_QR_CODE_MARGIN,
        public string $format = DEFAULT_QR_CODE_FORMAT,
        public string $errorCorrection = DEFAULT_QR_CODE_ERROR_CORRECTION,
        public bool $roundBlockSize = DEFAULT_QR_CODE_ROUND_BLOCK_SIZE,
        public bool $enabledForDisabledShortUrls = DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS,
    ) {
    }
}

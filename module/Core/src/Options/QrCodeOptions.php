<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_ERROR_CORRECTION;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_FORMAT;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_MARGIN;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ROUND_BLOCK_SIZE;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_SIZE;

final class QrCodeOptions
{
    public function __construct(
        public readonly int $size = DEFAULT_QR_CODE_SIZE,
        public readonly int $margin = DEFAULT_QR_CODE_MARGIN,
        public readonly string $format = DEFAULT_QR_CODE_FORMAT,
        public readonly string $errorCorrection = DEFAULT_QR_CODE_ERROR_CORRECTION,
        public readonly bool $roundBlockSize = DEFAULT_QR_CODE_ROUND_BLOCK_SIZE,
    ) {
    }
}

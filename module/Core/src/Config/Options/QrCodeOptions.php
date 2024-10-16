<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_BG_COLOR;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_COLOR;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ERROR_CORRECTION;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_FORMAT;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_MARGIN;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ROUND_BLOCK_SIZE;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_SIZE;

final readonly class QrCodeOptions
{
    public function __construct(
        public int $size = DEFAULT_QR_CODE_SIZE,
        public int $margin = DEFAULT_QR_CODE_MARGIN,
        public string $format = DEFAULT_QR_CODE_FORMAT,
        public string $errorCorrection = DEFAULT_QR_CODE_ERROR_CORRECTION,
        public bool $roundBlockSize = DEFAULT_QR_CODE_ROUND_BLOCK_SIZE,
        public bool $enabledForDisabledShortUrls = DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS,
        public string $color = DEFAULT_QR_CODE_COLOR,
        public string $bgColor = DEFAULT_QR_CODE_BG_COLOR,
        public ?string $logoUrl = null,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            size: (int) EnvVars::DEFAULT_QR_CODE_SIZE->loadFromEnv(),
            margin: (int) EnvVars::DEFAULT_QR_CODE_MARGIN->loadFromEnv(),
            format: EnvVars::DEFAULT_QR_CODE_FORMAT->loadFromEnv(),
            errorCorrection: EnvVars::DEFAULT_QR_CODE_ERROR_CORRECTION->loadFromEnv(),
            roundBlockSize: (bool) EnvVars::DEFAULT_QR_CODE_ROUND_BLOCK_SIZE->loadFromEnv(),
            enabledForDisabledShortUrls: (bool) EnvVars::QR_CODE_FOR_DISABLED_SHORT_URLS->loadFromEnv(),
            color: EnvVars::DEFAULT_QR_CODE_COLOR->loadFromEnv(),
            bgColor: EnvVars::DEFAULT_QR_CODE_BG_COLOR->loadFromEnv(),
            logoUrl: EnvVars::DEFAULT_QR_CODE_LOGO_URL->loadFromEnv(),
        );
    }
}

<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'qr_codes' => [
        'size' => (int) EnvVars::DEFAULT_QR_CODE_SIZE->loadFromEnv(),
        'margin' => (int) EnvVars::DEFAULT_QR_CODE_MARGIN->loadFromEnv(),
        'format' => EnvVars::DEFAULT_QR_CODE_FORMAT->loadFromEnv(),
        'error_correction' => EnvVars::DEFAULT_QR_CODE_ERROR_CORRECTION->loadFromEnv(),
        'round_block_size' => (bool) EnvVars::DEFAULT_QR_CODE_ROUND_BLOCK_SIZE->loadFromEnv(),
        'enabled_for_disabled_short_urls' => (bool) EnvVars::QR_CODE_FOR_DISABLED_SHORT_URLS->loadFromEnv(),
        'color' => EnvVars::DEFAULT_QR_CODE_COLOR->loadFromEnv(),
        'bg_color' => EnvVars::DEFAULT_QR_CODE_BG_COLOR->loadFromEnv(),
        'logo_url' => EnvVars::DEFAULT_QR_CODE_LOGO_URL->loadFromEnv(),
    ],

];

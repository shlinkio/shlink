<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_ERROR_CORRECTION;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_FORMAT;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_MARGIN;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ROUND_BLOCK_SIZE;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_SIZE;

return [

    'qr_codes' => [
        'size' => (int) EnvVars::DEFAULT_QR_CODE_SIZE()->loadFromEnv(DEFAULT_QR_CODE_SIZE),
        'margin' => (int) EnvVars::DEFAULT_QR_CODE_MARGIN()->loadFromEnv(DEFAULT_QR_CODE_MARGIN),
        'format' => EnvVars::DEFAULT_QR_CODE_FORMAT()->loadFromEnv(DEFAULT_QR_CODE_FORMAT),
        'error_correction' => EnvVars::DEFAULT_QR_CODE_ERROR_CORRECTION()->loadFromEnv(
            DEFAULT_QR_CODE_ERROR_CORRECTION,
        ),
        'round_block_size' => (bool) EnvVars::DEFAULT_QR_CODE_ROUND_BLOCK_SIZE()->loadFromEnv(
            DEFAULT_QR_CODE_ROUND_BLOCK_SIZE,
        ),
    ],

];

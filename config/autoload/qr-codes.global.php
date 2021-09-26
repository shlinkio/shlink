<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_ERROR_CORRECTION;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_FORMAT;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_MARGIN;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_SIZE;

return [

    'qr_codes' => [
        'size' => (int) env('DEFAULT_QR_CODE_SIZE', DEFAULT_QR_CODE_SIZE),
        'margin' => (int) env('DEFAULT_QR_CODE_MARGIN', DEFAULT_QR_CODE_MARGIN),
        'format' => env('DEFAULT_QR_CODE_FORMAT', DEFAULT_QR_CODE_FORMAT),
        'error_correction' => env('DEFAULT_QR_CODE_ERROR_CORRECTION', DEFAULT_QR_CODE_ERROR_CORRECTION),
    ],

];

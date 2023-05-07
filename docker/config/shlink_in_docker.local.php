<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Shlinkio\Shlink\Common\Logger\LoggerType;

return [

    'logger' => [
        'Shlink' => [
            'type' => LoggerType::STREAM->value,
            'destination' => 'php://stderr',
        ],
    ],

];

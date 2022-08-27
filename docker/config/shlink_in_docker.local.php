<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Shlinkio\Shlink\Common\Logger\LoggerType;

use function Shlinkio\Shlink\Config\runningInRoadRunner;

return [

    'logger' => [
        'Shlink' => [
            'type' => LoggerType::STREAM->value,
            'destination' => runningInRoadRunner() ? 'php://stderr' : 'php://stdout',
        ],
    ],

];

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\Stdlib\ArrayUtils\MergeReplaceKey;
use Shlinkio\Shlink\Common\Middleware\AccessLogMiddleware;

return [

    'access_logs' => [
        'ignored_paths' => [
            Action\HealthAction::ROUTE_PATH,
        ],
    ],

    // This config needs to go in this file in order to override the value defined in shlink-common
    ConfigAbstractFactory::class => [
        // Use MergeReplaceKey to overwrite what was defined in shlink-common, instead of merging it
        AccessLogMiddleware::class => new MergeReplaceKey(
            [AccessLogMiddleware::LOGGER_SERVICE_NAME, 'config.access_logs.ignored_paths'],
        ),
    ],

];

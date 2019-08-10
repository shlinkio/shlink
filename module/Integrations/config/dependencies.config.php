<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Integrations;

use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;

return [

    'dependencies' => [
        'factories' => [
            ImplicitOptionsMiddleware::class => Middleware\EmptyResponseImplicitOptionsMiddlewareFactory::class,
        ],
    ],

];

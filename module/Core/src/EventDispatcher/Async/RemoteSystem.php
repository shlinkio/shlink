<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Async;

enum RemoteSystem: string
{
    case MERCURE = 'Mercure';
    case RABBIT_MQ = 'RabbitMQ';
    case REDIS_PUB_SUB = 'Redis pub/sub';
}

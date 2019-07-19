<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher\Async;

use Psr\Container\ContainerInterface;

interface TaskInterface
{
    public function run(ContainerInterface $container): void;

    public function toString(): string;
}

<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\EventDispatcher\Async;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionObject;
use Shlinkio\Shlink\EventDispatcher\Async\TaskRunner;
use Shlinkio\Shlink\EventDispatcher\Async\TaskRunnerFactory;

class TaskRunnerFactoryTest extends TestCase
{
    /** @var TaskRunnerFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new TaskRunnerFactory();
    }

    /** @test */
    public function properlyCreatesService(): void
    {
        $loggerMock = $this->prophesize(LoggerInterface::class);
        $logger = $loggerMock->reveal();
        $containerMock = $this->prophesize(ContainerInterface::class);
        $getLogger = $containerMock->get(LoggerInterface::class)->willReturn($logger);
        $container = $containerMock->reveal();

        $taskRunner = ($this->factory)($container, '');
        $loggerProp = $this->getPropertyFromTaskRunner($taskRunner, 'logger');
        $containerProp = $this->getPropertyFromTaskRunner($taskRunner, 'container');

        $this->assertSame($container, $containerProp);
        $this->assertSame($logger, $loggerProp);
        $getLogger->shouldHaveBeenCalledOnce();
    }

    private function getPropertyFromTaskRunner(TaskRunner $taskRunner, string $propertyName)
    {
        $ref = new ReflectionObject($taskRunner);
        $prop = $ref->getProperty($propertyName);
        $prop->setAccessible(true);
        return $prop->getValue($taskRunner);
    }
}

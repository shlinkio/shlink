<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\EventDispatcher;

use Interop\Container\ContainerInterface;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Shlinkio\Shlink\Common\EventDispatcher\ListenerProviderFactory;

use function Phly\EventDispatcher\lazyListener;

class ListenerProviderFactoryTest extends TestCase
{
    /** @var ListenerProviderFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new ListenerProviderFactory();
    }

    /**
     * @test
     * @dataProvider provideContainersWithoutEvents
     */
    public function noListenersAreAttachedWhenNoConfigOrEventsAreRegistered(ContainerInterface $container): void
    {
        $provider = ($this->factory)($container, '');
        $listeners = $this->getListenersFromProvider($provider);

        $this->assertInstanceOf(AttachableListenerProvider::class, $provider);
        $this->assertEmpty($listeners);
    }

    public function provideContainersWithoutEvents(): iterable
    {
        yield 'no config' => [(function () {
            $container = $this->prophesize(ContainerInterface::class);
            $container->has('config')->willReturn(false);

            return $container->reveal();
        })()];
        yield 'no events' => [(function () {
            $container = $this->prophesize(ContainerInterface::class);
            $container->has('config')->willReturn(true);
            $container->get('config')->willReturn([]);

            return $container->reveal();
        })()];
    }

    /** @test */
    public function configuredEventsAreProperlyAttached(): void
    {
        $containerMock = $this->prophesize(ContainerInterface::class);
        $containerMock->has('config')->willReturn(true);
        $containerMock->get('config')->willReturn([
            'events' => [
                'foo' => [
                    'bar',
                    'baz',
                ],
                'something' => [
                    'some_listener',
                    'another_listener',
                    'foobar',
                ],
            ],
        ]);
        $container = $containerMock->reveal();

        $provider = ($this->factory)($container, '');
        $listeners = $this->getListenersFromProvider($provider);

        $this->assertInstanceOf(AttachableListenerProvider::class, $provider);
        $this->assertEquals([
            'foo' => [
                lazyListener($container, 'bar'),
                lazyListener($container, 'baz'),
            ],
            'something' => [
                lazyListener($container, 'some_listener'),
                lazyListener($container, 'another_listener'),
                lazyListener($container, 'foobar'),
            ],
        ], $listeners);
    }

    private function getListenersFromProvider($provider): array
    {
        $ref = new ReflectionObject($provider);
        $prop = $ref->getProperty('listeners');
        $prop->setAccessible(true);

        return $prop->getValue($provider);
    }
}

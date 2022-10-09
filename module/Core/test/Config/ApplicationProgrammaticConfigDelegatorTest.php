<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Route;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Core\Config\ApplicationProgrammaticConfigDelegator;

class ApplicationProgrammaticConfigDelegatorTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $app;
    private ObjectProphecy $container;

    protected function setUp(): void
    {
        $this->app = $this->prophesize(Application::class);
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->container->get(MiddlewareFactory::class)->willReturn(
            $this->prophesize(MiddlewareFactory::class)->reveal(),
        );
        $this->container->get('config')->willReturn([]);
    }

    /** @test */
    public function registersRoutesAndMiddlewares(): void
    {
        $routeMock = $this->prophesize(Route::class)->reveal();

        $route = $this->app->route(Argument::cetera())->willReturn($routeMock);
        $get = $this->app->get(Argument::cetera())->willReturn($routeMock);

        (new ApplicationProgrammaticConfigDelegator())($this->container->reveal(), '', fn () => $this->app->reveal());

        $this->app->pipe(Argument::cetera())->shouldHaveBeenCalledTimes(7);
        $route->shouldHaveBeenCalledTimes(21);
        $get->shouldHaveBeenCalledTimes(4);
    }
}

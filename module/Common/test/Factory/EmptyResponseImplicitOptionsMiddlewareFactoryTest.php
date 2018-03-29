<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Factory;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Factory\EmptyResponseImplicitOptionsMiddlewareFactory;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\ServiceManager\ServiceManager;

class EmptyResponseImplicitOptionsMiddlewareFactoryTest extends TestCase
{
    /**
     * @var EmptyResponseImplicitOptionsMiddlewareFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new EmptyResponseImplicitOptionsMiddlewareFactory();
    }

    /**
     * @test
     */
    public function serviceIsCreated()
    {
        $instance = $this->factory->__invoke(new ServiceManager(), '');
        $this->assertInstanceOf(ImplicitOptionsMiddleware::class, $instance);
    }

    /**
     * @test
     */
    public function responsePrototypeIsEmptyResponse()
    {
        $instance = $this->factory->__invoke(new ServiceManager(), '');

        $ref = new \ReflectionObject($instance);
        $prop = $ref->getProperty('responseFactory');
        $prop->setAccessible(true);
        $this->assertInstanceOf(EmptyResponse::class, $prop->getValue($instance)());
    }
}

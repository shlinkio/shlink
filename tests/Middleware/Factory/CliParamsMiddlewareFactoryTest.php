<?php
namespace AcelayaTest\UrlShortener\Middleware\Factory;

use Acelaya\UrlShortener\Middleware\CliParamsMiddleware;
use Acelaya\UrlShortener\Middleware\Factory\CliParamsMiddlewareFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;

class CliParamsMiddlewareFactoryTest extends TestCase
{
    /**
     * @var CliParamsMiddlewareFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new CliParamsMiddlewareFactory();
    }

    /**
     * @test
     */
    public function serviceIsCreated()
    {
        $instance = $this->factory->__invoke(new ServiceManager(), '');
        $this->assertInstanceOf(CliParamsMiddleware::class, $instance);
    }
}

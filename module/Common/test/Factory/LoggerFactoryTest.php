<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Factory;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Factory\LoggerFactory;
use Zend\ServiceManager\ServiceManager;

class LoggerFactoryTest extends TestCase
{
    /**
     * @var LoggerFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new LoggerFactory();
    }

    /**
     * @test
     */
    public function serviceIsCreated()
    {
        /** @var Logger $instance */
        $instance = $this->factory->__invoke(new ServiceManager(), '');
        $this->assertInstanceOf(LoggerInterface::class, $instance);
        $this->assertEquals('Logger', $instance->getName());
    }

    /**
     * @test
     */
    public function nameIsSetFromOptions()
    {
        /** @var Logger $instance */
        $instance = $this->factory->__invoke(new ServiceManager(), '', ['logger_name' => 'Foo']);
        $this->assertInstanceOf(LoggerInterface::class, $instance);
        $this->assertEquals('Foo', $instance->getName());
    }

    /**
     * @test
     */
    public function serviceNameOverwritesOptionsLoggerName()
    {
        /** @var Logger $instance */
        $instance = $this->factory->__invoke(new ServiceManager(), 'Logger_Shlink', ['logger_name' => 'Foo']);
        $this->assertInstanceOf(LoggerInterface::class, $instance);
        $this->assertEquals('Shlink', $instance->getName());
    }
}

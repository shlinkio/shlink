<?php
namespace ShlinkioTest\Shlink\Common\ErrorHandler;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Common\ErrorHandler\ErrorHandlerManager;
use Zend\ServiceManager\ServiceManager;

class ErrorHandlerManagerTest extends TestCase
{
    /**
     * @var ErrorHandlerManager
     */
    protected $pluginManager;

    public function setUp()
    {
        $this->pluginManager = new ErrorHandlerManager(new ServiceManager(), [
            'services' => [
                'foo' => function () {
                },
            ],
            'invokables' => [
                'invalid' => \stdClass::class,
            ]
        ]);
    }

    /**
     * @test
     */
    public function callablesAreReturned()
    {
        $instance = $this->pluginManager->get('foo');
        $this->assertInstanceOf(\Closure::class, $instance);
    }

    /**
     * @test
     * @expectedException \Zend\ServiceManager\Exception\InvalidServiceException
     */
    public function nonCallablesThrowException()
    {
        $this->pluginManager->get('invalid');
    }
}

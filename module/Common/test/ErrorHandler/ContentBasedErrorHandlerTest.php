<?php
namespace ShlinkioTest\Shlink\Common\ErrorHandler;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Common\ErrorHandler\ContentBasedErrorHandler;
use Shlinkio\Shlink\Common\ErrorHandler\ErrorHandlerManager;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\ServiceManager\ServiceManager;

class ContentBasedErrorHandlerTest extends TestCase
{
    /**
     * @var ContentBasedErrorHandler
     */
    protected $errorHandler;

    public function setUp()
    {
        $this->errorHandler = new ContentBasedErrorHandler(new ErrorHandlerManager(new ServiceManager(), [
            'factories' => [
                'text/html' => [$this, 'factory'],
                'application/json' => [$this, 'factory'],
            ],
        ]));
    }

    public function factory($container, $name)
    {
        return function () use ($name) {
            return $name;
        };
    }

    /**
     * @test
     */
    public function correctAcceptHeaderValueInvokesErrorHandler()
    {
        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept', 'foo/bar,application/json');
        $result = $this->errorHandler->__invoke($request, new Response());
        $this->assertEquals('application/json', $result);
    }

    /**
     * @test
     */
    public function defaultContentTypeIsUsedWhenNoAcceptHeaderisPresent()
    {
        $request = ServerRequestFactory::fromGlobals();
        $result = $this->errorHandler->__invoke($request, new Response());
        $this->assertEquals('text/html', $result);
    }

    /**
     * @test
     */
    public function defaultContentTypeIsUsedWhenAcceptedContentIsNotSupported()
    {
        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept', 'foo/bar,text/xml');
        $result = $this->errorHandler->__invoke($request, new Response());
        $this->assertEquals('text/html', $result);
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Common\Exception\InvalidArgumentException
     */
    public function ifNoErrorHandlerIsFoundAnExceptionIsThrown()
    {
        $this->errorHandler = new ContentBasedErrorHandler(new ErrorHandlerManager(new ServiceManager(), []));
        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept', 'foo/bar,text/xml');
        $result = $this->errorHandler->__invoke($request, new Response());
    }
}

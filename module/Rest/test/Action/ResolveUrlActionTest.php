<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ResolveUrlAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class ResolveUrlActionTest extends TestCase
{
    /**
     * @var ResolveUrlAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $urlShortener;

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new ResolveUrlAction($this->urlShortener->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function incorrectShortCodeReturnsError()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(EntityDoesNotExistException::class)
                                                       ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $response = $this->action->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::INVALID_ARGUMENT_ERROR) > 0);
    }

    /**
     * @test
     */
    public function correctShortCodeReturnsSuccess()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn('http://domain.com/foo/bar')
                                                       ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $response = $this->action->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), 'http://domain.com/foo/bar') > 0);
    }

    /**
     * @test
     */
    public function invalidShortCodeExceptionReturnsError()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(InvalidShortCodeException::class)
                                                       ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $response = $this->action->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::INVALID_SHORTCODE_ERROR) > 0);
    }

    /**
     * @test
     */
    public function unexpectedExceptionWillReturnError()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(\Exception::class)
                                                       ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $response = $this->action->handle($request);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::UNKNOWN_ERROR) > 0);
    }
}

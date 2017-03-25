<?php
namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\CreateShortcodeAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;
use Zend\I18n\Translator\Translator;

class CreateShortcodeActionTest extends TestCase
{
    /**
     * @var CreateShortcodeAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $urlShortener;

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new CreateShortcodeAction($this->urlShortener->reveal(), Translator::factory([]), [
            'schema' => 'http',
            'hostname' => 'foo.com',
        ]);
    }

    /**
     * @test
     */
    public function missingLongUrlParamReturnsError()
    {
        $response = $this->action->process(
            ServerRequestFactory::fromGlobals(),
            TestUtils::createDelegateMock()->reveal()
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function properShortcodeConversionReturnsData()
    {
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'))
            ->willReturn('abc123')
            ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->process($request, TestUtils::createDelegateMock()->reveal());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), 'http://foo.com/abc123') > 0);
    }

    /**
     * @test
     */
    public function anInvalidUrlReturnsError()
    {
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'))
            ->willThrow(InvalidUrlException::class)
            ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->process($request, TestUtils::createDelegateMock()->reveal());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::INVALID_URL_ERROR) > 0);
    }

    /**
     * @test
     */
    public function aGenericExceptionWillReturnError()
    {
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'))
            ->willThrow(\Exception::class)
            ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->process($request, TestUtils::createDelegateMock()->reveal());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::UNKNOWN_ERROR) > 0);
    }
}

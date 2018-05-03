<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\CreateShortCodeAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;
use Zend\I18n\Translator\Translator;

class CreateShortCodeActionTest extends TestCase
{
    /**
     * @var CreateShortCodeAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $urlShortener;

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new CreateShortCodeAction($this->urlShortener->reveal(), Translator::factory([]), [
            'schema' => 'http',
            'hostname' => 'foo.com',
        ]);
    }

    /**
     * @test
     */
    public function missingLongUrlParamReturnsError()
    {
        $response = $this->action->handle(ServerRequestFactory::fromGlobals());
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function properShortcodeConversionReturnsData()
    {
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'), Argument::cetera())
            ->willReturn('abc123')
            ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), 'http://foo.com/abc123') > 0);
    }

    /**
     * @test
     */
    public function anInvalidUrlReturnsError()
    {
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'), Argument::cetera())
            ->willThrow(InvalidUrlException::class)
            ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::INVALID_URL_ERROR) > 0);
    }

    /**
     * @test
     */
    public function nonUniqueSlugReturnsError()
    {
        $this->urlShortener->urlToShortCode(
            Argument::type(Uri::class),
            Argument::type('array'),
            null,
            null,
            'foo',
            Argument::cetera()
        )->willThrow(NonUniqueSlugException::class)->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
            'customSlug' => 'foo',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertContains(RestUtils::INVALID_SLUG_ERROR, (string) $response->getBody());
    }

    /**
     * @test
     */
    public function aGenericExceptionWillReturnError()
    {
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'), Argument::cetera())
            ->willThrow(\Exception::class)
            ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::UNKNOWN_ERROR) > 0);
    }
}

<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\CreateShortUrlAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use function strpos;

class CreateShortUrlActionTest extends TestCase
{
    /** @var CreateShortUrlAction */
    private $action;
    /** @var ObjectProphecy */
    private $urlShortener;

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new CreateShortUrlAction($this->urlShortener->reveal(), [
            'schema' => 'http',
            'hostname' => 'foo.com',
        ]);
    }

    /**
     * @test
     */
    public function missingLongUrlParamReturnsError()
    {
        $response = $this->action->handle(new ServerRequest());
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function properShortcodeConversionReturnsData()
    {
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'), Argument::cetera())
            ->willReturn(
                (new ShortUrl(''))->setShortCode('abc123')
            )
            ->shouldBeCalledOnce();

        $request = (new ServerRequest())->withParsedBody([
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
            ->shouldBeCalledOnce();

        $request = (new ServerRequest())->withParsedBody([
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
            ShortUrlMeta::createFromRawData(['customSlug' => 'foo']),
            Argument::cetera()
        )->willThrow(NonUniqueSlugException::class)->shouldBeCalledOnce();

        $request = (new ServerRequest())->withParsedBody([
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
            ->willThrow(Exception::class)
            ->shouldBeCalledOnce();

        $request = (new ServerRequest())->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::UNKNOWN_ERROR) > 0);
    }
}

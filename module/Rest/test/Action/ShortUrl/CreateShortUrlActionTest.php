<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\CreateShortUrlAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

use function strpos;

class CreateShortUrlActionTest extends TestCase
{
    private const DOMAIN_CONFIG = [
        'schema' => 'http',
        'hostname' => 'foo.com',
    ];

    /** @var CreateShortUrlAction */
    private $action;
    /** @var ObjectProphecy */
    private $urlShortener;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new CreateShortUrlAction($this->urlShortener->reveal(), self::DOMAIN_CONFIG);
    }

    /** @test */
    public function missingLongUrlParamReturnsError(): void
    {
        $response = $this->action->handle(new ServerRequest());
        $this->assertEquals(400, $response->getStatusCode());
    }

    /** @test */
    public function properShortcodeConversionReturnsData(): void
    {
        $shortUrl = new ShortUrl('');
        $this->urlShortener->urlToShortCode(Argument::type(Uri::class), Argument::type('array'), Argument::cetera())
             ->willReturn($shortUrl)
             ->shouldBeCalledOnce();

        $request = (new ServerRequest())->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), $shortUrl->toString(self::DOMAIN_CONFIG)) > 0);
    }

    /**
     * @test
     * @dataProvider provideInvalidDomains
     */
    public function anInvalidDomainReturnsError(string $domain): void
    {
        $shortUrl = new ShortUrl('');
        $urlToShortCode = $this->urlShortener->urlToShortCode(Argument::cetera())->willReturn($shortUrl);

        $request = (new ServerRequest())->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
            'domain' => $domain,
        ]);
        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        $payload = $response->getPayload();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(RestUtils::INVALID_ARGUMENT_ERROR, $payload['error']);
        $urlToShortCode->shouldNotHaveBeenCalled();
    }

    public function provideInvalidDomains(): iterable
    {
        yield ['localhost:80000'];
        yield ['127.0.0.1'];
        yield ['???/&%$&'];
    }

    /** @test */
    public function nonUniqueSlugReturnsError(): void
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
        $this->assertStringContainsString(RestUtils::INVALID_SLUG_ERROR, (string) $response->getBody());
    }
}

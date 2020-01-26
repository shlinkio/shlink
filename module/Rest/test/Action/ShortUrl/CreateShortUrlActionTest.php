<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\CreateShortUrlAction;

use function strpos;

class CreateShortUrlActionTest extends TestCase
{
    private const DOMAIN_CONFIG = [
        'schema' => 'http',
        'hostname' => 'foo.com',
    ];

    private CreateShortUrlAction $action;
    private ObjectProphecy $urlShortener;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new CreateShortUrlAction($this->urlShortener->reveal(), self::DOMAIN_CONFIG);
    }

    /** @test */
    public function missingLongUrlParamReturnsError(): void
    {
        $this->expectException(ValidationException::class);
        $this->action->handle(new ServerRequest());
    }

    /**
     * @test
     * @dataProvider provideRequestBodies
     */
    public function properShortcodeConversionReturnsData(array $body, ShortUrlMeta $expectedMeta): void
    {
        $shortUrl = new ShortUrl('');
        $shorten = $this->urlShortener->urlToShortCode(
            Argument::type(Uri::class),
            Argument::type('array'),
            $expectedMeta,
        )->willReturn($shortUrl);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body);
        $response = $this->action->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), $shortUrl->toString(self::DOMAIN_CONFIG)) > 0);
        $shorten->shouldHaveBeenCalledOnce();
    }

    public function provideRequestBodies(): iterable
    {
        $fullMeta = [
            'longUrl' => 'http://www.domain.com/foo/bar',
            'validSince' => Chronos::now()->toAtomString(),
            'validUntil' => Chronos::now()->toAtomString(),
            'customSlug' => 'foo-bar-baz',
            'maxVisits' => 50,
            'findIfExists' => true,
            'domain' => 'my-domain.com',
        ];

        yield [['longUrl' => 'http://www.domain.com/foo/bar'], ShortUrlMeta::createEmpty()];
        yield [$fullMeta, ShortUrlMeta::fromRawData($fullMeta)];
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

        $this->expectException(ValidationException::class);
        $urlToShortCode->shouldNotBeCalled();

        $this->action->handle($request);
    }

    public function provideInvalidDomains(): iterable
    {
        yield ['localhost:80000'];
        yield ['127.0.0.1'];
        yield ['???/&%$&'];
    }
}

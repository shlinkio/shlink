<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\CreateShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function strpos;

class CreateShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

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
    public function properShortcodeConversionReturnsData(): void
    {
        $apiKey = new ApiKey();
        $shortUrl = ShortUrl::createEmpty();
        $expectedMeta = $body = [
            'longUrl' => 'http://www.domain.com/foo/bar',
            'validSince' => Chronos::now()->toAtomString(),
            'validUntil' => Chronos::now()->toAtomString(),
            'customSlug' => 'foo-bar-baz',
            'maxVisits' => 50,
            'findIfExists' => true,
            'domain' => 'my-domain.com',
        ];
        $expectedMeta['apiKey'] = $apiKey;

        $shorten = $this->urlShortener->shorten(
            Argument::type('array'),
            ShortUrlMeta::fromRawData($expectedMeta),
        )->willReturn($shortUrl);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body)->withAttribute(ApiKey::class, $apiKey);

        $response = $this->action->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertTrue(strpos($response->getBody()->getContents(), $shortUrl->toString(self::DOMAIN_CONFIG)) > 0);
        $shorten->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideInvalidDomains
     */
    public function anInvalidDomainReturnsError(string $domain): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $urlToShortCode = $this->urlShortener->shorten(Argument::cetera())->willReturn($shortUrl);

        $request = (new ServerRequest())->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
            'domain' => $domain,
        ])->withAttribute(ApiKey::class, new ApiKey());

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

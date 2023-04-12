<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\UrlShorteningResult;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\CreateShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class CreateShortUrlActionTest extends TestCase
{
    private CreateShortUrlAction $action;
    private MockObject & UrlShortener $urlShortener;
    private MockObject & DataTransformerInterface $transformer;

    protected function setUp(): void
    {
        $this->urlShortener = $this->createMock(UrlShortener::class);
        $this->transformer = $this->createMock(DataTransformerInterface::class);

        $this->action = new CreateShortUrlAction($this->urlShortener, $this->transformer, new UrlShortenerOptions());
    }

    #[Test]
    public function properShortcodeConversionReturnsData(): void
    {
        $apiKey = ApiKey::create();
        $shortUrl = ShortUrl::createFake();
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

        $this->urlShortener->expects($this->once())->method('shorten')->with(
            ShortUrlCreation::fromRawData($expectedMeta),
        )->willReturn(UrlShorteningResult::withoutErrorOnEventDispatching($shortUrl));
        $this->transformer->expects($this->once())->method('transform')->with($shortUrl)->willReturn(
            ['shortUrl' => 'stringified_short_url'],
        );

        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body)->withAttribute(ApiKey::class, $apiKey);

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        $payload = $response->getPayload();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('stringified_short_url', $payload['shortUrl']);
    }

    #[Test, DataProvider('provideInvalidDomains')]
    public function anInvalidDomainReturnsError(string $domain): void
    {
        $this->urlShortener->expects($this->never())->method('shorten');
        $this->transformer->expects($this->never())->method('transform');

        $request = (new ServerRequest())->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
            'domain' => $domain,
        ])->withAttribute(ApiKey::class, ApiKey::create());

        $this->expectException(ValidationException::class);

        $this->action->handle($request);
    }

    public static function provideInvalidDomains(): iterable
    {
        yield ['localhost:80000'];
        yield ['127.0.0.1'];
        yield ['???/&%$&'];
    }
}

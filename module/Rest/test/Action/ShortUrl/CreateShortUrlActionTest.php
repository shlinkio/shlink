<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use CuyZ\Valinor\MapperBuilder;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\UrlShorteningResult;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformerInterface;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\CreateShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class CreateShortUrlActionTest extends TestCase
{
    private CreateShortUrlAction $action;
    private MockObject&UrlShortener $urlShortener;
    private MockObject&ShortUrlDataTransformerInterface $transformer;

    protected function setUp(): void
    {
        $this->urlShortener = $this->createMock(UrlShortener::class);
        $this->transformer = $this->createMock(ShortUrlDataTransformerInterface::class);
        $mapper = new MapperBuilder()->mapper();

        $this->action = new CreateShortUrlAction($this->urlShortener, $this->transformer, $mapper);
    }

    #[Test]
    public function properShortcodeConversionReturnsData(): void
    {
        $now = Chronos::now()->microsecond(0);
        $apiKey = ApiKey::create();
        $shortUrl = ShortUrl::createFake();
        $expectedCreation = new ShortUrlCreation(
            longUrl: 'http://www.domain.com/foo/bar',
            validSince: $now,
            validUntil: $now,
            customSlug: 'foo-bar-baz',
            maxVisits: 50,
            findIfExists: true,
            domain: 'my-domain.com',
            apiKey: $apiKey,
        );
        $body = [
            'longUrl' => $expectedCreation->longUrl,
            'validSince' => $now->toAtomString(),
            'validUntil' => $now->toAtomString(),
            'customSlug' => $expectedCreation->customSlug,
            'maxVisits' => $expectedCreation->maxVisits,
            'findIfExists' => $expectedCreation->findIfExists,
            'domain' => $expectedCreation->domain,
        ];

        $this->urlShortener
            ->expects($this->once())
            ->method('shorten')
            ->with(
                $expectedCreation,
            )
            ->willReturn(UrlShorteningResult::withoutErrorOnEventDispatching($shortUrl));
        $this->transformer
            ->expects($this->once())
            ->method('transform')
            ->with($shortUrl)
            ->willReturn(
                ['shortUrl' => 'stringified_short_url'],
            );

        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body)->withAttribute(ApiKey::class, $apiKey);

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        $payload = $response->getPayload();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('stringified_short_url', $payload['shortUrl']);
    }
}

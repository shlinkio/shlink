<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use CuyZ\Valinor\MapperBuilder;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\UrlShorteningResult;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformerInterface;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\SingleStepCreateShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class SingleStepCreateShortUrlActionTest extends TestCase
{
    private SingleStepCreateShortUrlAction $action;
    private MockObject & UrlShortenerInterface $urlShortener;

    protected function setUp(): void
    {
        $this->urlShortener = $this->createMock(UrlShortenerInterface::class);
        $transformer = $this->createStub(ShortUrlDataTransformerInterface::class);
        $transformer->method('transform')->willReturn([]);
        $mapper = new MapperBuilder()->mapper();

        $this->action = new SingleStepCreateShortUrlAction($this->urlShortener, $transformer, $mapper);
    }

    #[Test]
    public function properDataIsPassedWhenGeneratingShortCode(): void
    {
        $apiKey = ApiKey::create();

        $request = new ServerRequest()->withQueryParams([
            'longUrl' => 'http://foobar.com',
        ])->withAttribute(ApiKey::class, $apiKey);
        $this->urlShortener->expects($this->once())->method('shorten')->with(
            new ShortUrlCreation('http://foobar.com', apiKey: $apiKey),
        )->willReturn(UrlShorteningResult::withoutErrorOnEventDispatching(ShortUrl::createFake()));

        $resp = $this->action->handle($request);

        self::assertEquals(200, $resp->getStatusCode());
    }
}

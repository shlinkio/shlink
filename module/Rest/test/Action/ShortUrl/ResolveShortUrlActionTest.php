<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ResolveShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ResolveShortUrlActionTest extends TestCase
{
    private ResolveShortUrlAction $action;
    private MockObject & ShortUrlResolverInterface $urlResolver;

    protected function setUp(): void
    {
        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->action = new ResolveShortUrlAction($this->urlResolver, new ShortUrlDataTransformer(
            new ShortUrlStringifier(),
        ));
    }

    #[Test]
    public function correctShortCodeReturnsSuccess(): void
    {
        $shortCode = 'abc123';
        $apiKey = ApiKey::create();
        $this->urlResolver->expects($this->once())->method('resolveShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            $apiKey,
        )->willReturn(ShortUrl::withLongUrl('http://domain.com/foo/bar'));

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)->withAttribute(ApiKey::class, $apiKey);
        $response = $this->action->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('http://domain.com/foo/bar', $response->getBody()->getContents());
    }
}

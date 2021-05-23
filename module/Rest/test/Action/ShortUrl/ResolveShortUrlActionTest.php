<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ResolveShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ResolveShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

    private ResolveShortUrlAction $action;
    private ObjectProphecy $urlResolver;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->action = new ResolveShortUrlAction($this->urlResolver->reveal(), new ShortUrlDataTransformer(
            new ShortUrlStringifier([]),
        ));
    }

    /** @test */
    public function correctShortCodeReturnsSuccess(): void
    {
        $shortCode = 'abc123';
        $apiKey = ApiKey::create();
        $this->urlResolver->resolveShortUrl(new ShortUrlIdentifier($shortCode), $apiKey)->willReturn(
            ShortUrl::withLongUrl('http://domain.com/foo/bar'),
        )->shouldBeCalledOnce();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)->withAttribute(ApiKey::class, $apiKey);
        $response = $this->action->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('http://domain.com/foo/bar', $response->getBody()->getContents());
    }
}

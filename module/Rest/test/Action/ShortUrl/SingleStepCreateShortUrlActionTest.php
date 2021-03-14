<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\SingleStepCreateShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class SingleStepCreateShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

    private SingleStepCreateShortUrlAction $action;
    private ObjectProphecy $urlShortener;
    private ObjectProphecy $transformer;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortenerInterface::class);
        $this->transformer = $this->prophesize(DataTransformerInterface::class);
        $this->transformer->transform(Argument::type(ShortUrl::class))->willReturn([]);

        $this->action = new SingleStepCreateShortUrlAction(
            $this->urlShortener->reveal(),
            $this->transformer->reveal(),
        );
    }

    /** @test */
    public function properDataIsPassedWhenGeneratingShortCode(): void
    {
        $apiKey = ApiKey::create();

        $request = (new ServerRequest())->withQueryParams([
            'longUrl' => 'http://foobar.com',
        ])->withAttribute(ApiKey::class, $apiKey);
        $generateShortCode = $this->urlShortener->shorten(
            ShortUrlMeta::fromRawData(['apiKey' => $apiKey, 'longUrl' => 'http://foobar.com']),
        )->willReturn(ShortUrl::createEmpty());

        $resp = $this->action->handle($request);

        self::assertEquals(200, $resp->getStatusCode());
        $generateShortCode->shouldHaveBeenCalled();
    }
}

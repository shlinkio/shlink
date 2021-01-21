<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\SingleStepCreateShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class SingleStepCreateShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

    private SingleStepCreateShortUrlAction $action;
    private ObjectProphecy $urlShortener;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortenerInterface::class);

        $this->action = new SingleStepCreateShortUrlAction(
            $this->urlShortener->reveal(),
            [
                'schema' => 'http',
                'hostname' => 'foo.com',
            ],
        );
    }

    /** @test */
    public function errorResponseIsReturnedIfNoUrlIsProvided(): void
    {
        $request = new ServerRequest();

        $this->expectException(ValidationException::class);

        $this->action->handle($request);
    }

    /** @test */
    public function properDataIsPassedWhenGeneratingShortCode(): void
    {
        $apiKey = new ApiKey();

        $request = (new ServerRequest())->withQueryParams([
            'longUrl' => 'http://foobar.com',
        ])->withAttribute(ApiKey::class, $apiKey);
        $generateShortCode = $this->urlShortener->shorten(
            Argument::that(function (string $argument): bool {
                Assert::assertEquals('http://foobar.com', $argument);
                return true;
            }),
            [],
            ShortUrlMeta::fromRawData(['apiKey' => $apiKey]),
        )->willReturn(new ShortUrl(''));

        $resp = $this->action->handle($request);

        self::assertEquals(200, $resp->getStatusCode());
        $generateShortCode->shouldHaveBeenCalled();
    }
}

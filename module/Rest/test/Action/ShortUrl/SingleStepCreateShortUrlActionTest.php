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
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;

class SingleStepCreateShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

    private SingleStepCreateShortUrlAction $action;
    private ObjectProphecy $urlShortener;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortenerInterface::class);
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);

        $this->action = new SingleStepCreateShortUrlAction(
            $this->urlShortener->reveal(),
            $this->apiKeyService->reveal(),
            [
                'schema' => 'http',
                'hostname' => 'foo.com',
            ],
        );
    }

    /** @test */
    public function errorResponseIsReturnedIfInvalidApiKeyIsProvided(): void
    {
        $request = (new ServerRequest())->withQueryParams(['apiKey' => 'abc123']);
        $findApiKey = $this->apiKeyService->check('abc123')->willReturn(false);

        $this->expectException(ValidationException::class);
        $findApiKey->shouldBeCalledOnce();

        $this->action->handle($request);
    }

    /** @test */
    public function errorResponseIsReturnedIfNoUrlIsProvided(): void
    {
        $request = (new ServerRequest())->withQueryParams(['apiKey' => 'abc123']);
        $findApiKey = $this->apiKeyService->check('abc123')->willReturn(true);

        $this->expectException(ValidationException::class);
        $findApiKey->shouldBeCalledOnce();

        $this->action->handle($request);
    }

    /** @test */
    public function properDataIsPassedWhenGeneratingShortCode(): void
    {
        $request = (new ServerRequest())->withQueryParams([
            'apiKey' => 'abc123',
            'longUrl' => 'http://foobar.com',
        ]);
        $findApiKey = $this->apiKeyService->check('abc123')->willReturn(true);
        $generateShortCode = $this->urlShortener->urlToShortCode(
            Argument::that(function (string $argument): string {
                Assert::assertEquals('http://foobar.com', $argument);
                return $argument;
            }),
            [],
            ShortUrlMeta::createEmpty(),
        )->willReturn(new ShortUrl(''));

        $resp = $this->action->handle($request);

        self::assertEquals(200, $resp->getStatusCode());
        $findApiKey->shouldHaveBeenCalled();
        $generateShortCode->shouldHaveBeenCalled();
    }
}

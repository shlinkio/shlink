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
use Shlinkio\Shlink\Rest\Service\ApiKeyCheckResult;
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
        $findApiKey = $this->apiKeyService->check('abc123')->willReturn(new ApiKeyCheckResult());

        $this->expectException(ValidationException::class);
        $findApiKey->shouldBeCalledOnce();

        $this->action->handle($request);
    }

    /** @test */
    public function errorResponseIsReturnedIfNoUrlIsProvided(): void
    {
        $request = (new ServerRequest())->withQueryParams(['apiKey' => 'abc123']);
        $findApiKey = $this->apiKeyService->check('abc123')->willReturn(new ApiKeyCheckResult(new ApiKey()));

        $this->expectException(ValidationException::class);
        $findApiKey->shouldBeCalledOnce();

        $this->action->handle($request);
    }

    /** @test */
    public function properDataIsPassedWhenGeneratingShortCode(): void
    {
        $apiKey = new ApiKey();
        $key = $apiKey->toString();

        $request = (new ServerRequest())->withQueryParams([
            'apiKey' => $key,
            'longUrl' => 'http://foobar.com',
        ]);
        $findApiKey = $this->apiKeyService->check($key)->willReturn(new ApiKeyCheckResult($apiKey));
        $generateShortCode = $this->urlShortener->shorten(
            Argument::that(function (string $argument): string {
                Assert::assertEquals('http://foobar.com', $argument);
                return $argument;
            }),
            [],
            ShortUrlMeta::fromRawData(['apiKey' => $key]),
        )->willReturn(new ShortUrl(''));

        $resp = $this->action->handle($request);

        self::assertEquals(200, $resp->getStatusCode());
        $findApiKey->shouldHaveBeenCalled();
        $generateShortCode->shouldHaveBeenCalled();
    }
}

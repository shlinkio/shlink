<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\SingleStepCreateShortUrlAction;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Zend\Diactoros\ServerRequest;

class SingleStepCreateShortUrlActionTest extends TestCase
{
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
            ]
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
            Argument::that(function (UriInterface $argument) {
                Assert::assertEquals('http://foobar.com', (string) $argument);
                return $argument;
            }),
            [],
            ShortUrlMeta::createEmpty()
        )->willReturn(new ShortUrl(''));

        $resp = $this->action->handle($request);

        $this->assertEquals(200, $resp->getStatusCode());
        $findApiKey->shouldHaveBeenCalled();
        $generateShortCode->shouldHaveBeenCalled();
    }
}

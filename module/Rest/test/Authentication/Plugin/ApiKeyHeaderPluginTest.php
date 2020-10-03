<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Authentication\Plugin;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\ApiKeyHeaderPlugin;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;

class ApiKeyHeaderPluginTest extends TestCase
{
    private ApiKeyHeaderPlugin $plugin;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $this->plugin = new ApiKeyHeaderPlugin($this->apiKeyService->reveal());
    }

    /** @test */
    public function verifyThrowsExceptionWhenApiKeyIsNotValid(): void
    {
        $apiKey = 'abc-ABC';
        $check = $this->apiKeyService->check($apiKey)->willReturn(false);
        $check->shouldBeCalledOnce();

        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage('Provided API key does not exist or is invalid');

        $this->plugin->verify($this->createRequest($apiKey));
    }

    /** @test */
    public function verifyDoesNotThrowExceptionWhenApiKeyIsValid(): void
    {
        $apiKey = 'abc-ABC';
        $check = $this->apiKeyService->check($apiKey)->willReturn(true);

        $this->plugin->verify($this->createRequest($apiKey));

        $check->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function updateReturnsResponseAsIs(): void
    {
        $apiKey = 'abc-ABC';
        $response = new Response();

        $returnedResponse = $this->plugin->update($this->createRequest($apiKey), $response);

        self::assertSame($response, $returnedResponse);
    }

    private function createRequest(string $apiKey): ServerRequestInterface
    {
        return (new ServerRequest())->withHeader(ApiKeyHeaderPlugin::HEADER_NAME, $apiKey);
    }
}

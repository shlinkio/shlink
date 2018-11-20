<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Authentication\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\ApiKeyHeaderPlugin;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class ApiKeyHeaderPluginTest extends TestCase
{
    /** @var ApiKeyHeaderPlugin */
    private $plugin;
    /** @var ObjectProphecy */
    private $apiKeyService;

    public function setUp()
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $this->plugin = new ApiKeyHeaderPlugin($this->apiKeyService->reveal());
    }

    /**
     * @test
     */
    public function verifyThrowsExceptionWhenApiKeyIsNotValid()
    {
        $apiKey = 'abc-ABC';
        $check = $this->apiKeyService->check($apiKey)->willReturn(false);
        $check->shouldBeCalledOnce();

        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage('Provided API key does not exist or is invalid');

        $this->plugin->verify($this->createRequest($apiKey));
    }

    /**
     * @test
     */
    public function verifyDoesNotThrowExceptionWhenApiKeyIsValid()
    {
        $apiKey = 'abc-ABC';
        $check = $this->apiKeyService->check($apiKey)->willReturn(true);

        $this->plugin->verify($this->createRequest($apiKey));

        $check->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function updateReturnsResponseAsIs()
    {
        $apiKey = 'abc-ABC';
        $response = new Response();

        $returnedResponse = $this->plugin->update($this->createRequest($apiKey), $response);

        $this->assertSame($response, $returnedResponse);
    }

    private function createRequest(string $apiKey): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withHeader(ApiKeyHeaderPlugin::HEADER_NAME, $apiKey);
    }
}

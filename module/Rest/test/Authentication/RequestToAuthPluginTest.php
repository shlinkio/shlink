<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Authentication;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Authentication\AuthenticationPluginManagerInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\ApiKeyHeaderPlugin;
use Shlinkio\Shlink\Rest\Authentication\Plugin\AuthenticationPluginInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\AuthorizationHeaderPlugin;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPlugin;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;
use Zend\Diactoros\ServerRequest;

use function implode;
use function sprintf;

class RequestToAuthPluginTest extends TestCase
{
    /** @var RequestToHttpAuthPlugin */
    private $requestToPlugin;
    /** @var ObjectProphecy */
    private $pluginManager;

    public function setUp(): void
    {
        $this->pluginManager = $this->prophesize(AuthenticationPluginManagerInterface::class);
        $this->requestToPlugin = new RequestToHttpAuthPlugin($this->pluginManager->reveal());
    }

    /** @test */
    public function exceptionIsFoundWhenNoneOfTheSupportedMethodsIsFound(): void
    {
        $request = new ServerRequest();

        $this->expectException(MissingAuthenticationException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected one of the following authentication headers, but none were provided, ["%s"]',
            implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)
        ));

        $this->requestToPlugin->fromRequest($request);
    }

    /**
     * @test
     * @dataProvider provideHeaders
     */
    public function properPluginIsFetchedWhenAnyAuthTypeIsFound(array $headers, string $expectedHeader): void
    {
        $request = new ServerRequest();
        foreach ($headers as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        $plugin = $this->prophesize(AuthenticationPluginInterface::class);
        $getPlugin = $this->pluginManager->get($expectedHeader)->willReturn($plugin->reveal());

        $this->requestToPlugin->fromRequest($request);

        $getPlugin->shouldHaveBeenCalledOnce();
    }

    public function provideHeaders(): iterable
    {
        yield 'API key header only' => [[
            ApiKeyHeaderPlugin::HEADER_NAME => 'foobar',
        ], ApiKeyHeaderPlugin::HEADER_NAME];
        yield 'Authorization header only' => [[
            AuthorizationHeaderPlugin::HEADER_NAME => 'foobar',
        ], AuthorizationHeaderPlugin::HEADER_NAME];
        yield 'Both headers' => [[
            AuthorizationHeaderPlugin::HEADER_NAME => 'foobar',
            ApiKeyHeaderPlugin::HEADER_NAME => 'foobar',
        ], ApiKeyHeaderPlugin::HEADER_NAME];
    }
}

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
use Shlinkio\Shlink\Rest\Exception\NoAuthenticationException;
use Zend\Diactoros\ServerRequestFactory;
use function implode;
use function sprintf;

class RequestToAuthPluginTest extends TestCase
{
    /**
     * @var RequestToHttpAuthPlugin
     */
    private $requestToPlugin;
    /**
     * @var ObjectProphecy
     */
    private $pluginManager;

    public function setUp()
    {
        $this->pluginManager = $this->prophesize(AuthenticationPluginManagerInterface::class);
        $this->requestToPlugin = new RequestToHttpAuthPlugin($this->pluginManager->reveal());
    }

    /**
     * @test
     */
    public function exceptionIsFoundWhenNoneOfTheSupportedMethodsIsFound()
    {
        $request = ServerRequestFactory::fromGlobals();

        $this->expectException(NoAuthenticationException::class);
        $this->expectExceptionMessage(sprintf(
            'None of the valid authentication mechanisms where provided. Expected one of ["%s"]',
            implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)
        ));

        $this->requestToPlugin->fromRequest($request);
    }

    /**
     * @test
     * @dataProvider provideHeaders
     */
    public function properPluginIsFetchedWhenAnyAuthTypeIsFound(array $headers, string $expectedHeader)
    {
        $request = ServerRequestFactory::fromGlobals();
        foreach ($headers as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        $plugin = $this->prophesize(AuthenticationPluginInterface::class);
        $getPlugin = $this->pluginManager->get($expectedHeader)->willReturn($plugin->reveal());

        $this->requestToPlugin->fromRequest($request);

        $getPlugin->shouldHaveBeenCalledTimes(1);
    }

    public function provideHeaders(): array
    {
        return [
            'API key header only' => [[
                ApiKeyHeaderPlugin::HEADER_NAME => 'foobar',
            ], ApiKeyHeaderPlugin::HEADER_NAME],
            'Authorization header only' => [[
                AuthorizationHeaderPlugin::HEADER_NAME => 'foobar',
            ], AuthorizationHeaderPlugin::HEADER_NAME],
            'Both headers' => [[
                AuthorizationHeaderPlugin::HEADER_NAME => 'foobar',
                ApiKeyHeaderPlugin::HEADER_NAME => 'foobar',
            ], ApiKeyHeaderPlugin::HEADER_NAME],
        ];
    }
}

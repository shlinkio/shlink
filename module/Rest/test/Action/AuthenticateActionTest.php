<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Action\AuthenticateAction;
use Shlinkio\Shlink\Rest\Authentication\JWTService;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class AuthenticateActionTest extends TestCase
{
    /**
     * @var AuthenticateAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $apiKeyService;
    /**
     * @var ObjectProphecy
     */
    protected $jwtService;

    public function setUp()
    {
        $this->apiKeyService = $this->prophesize(ApiKeyService::class);
        $this->jwtService = $this->prophesize(JWTService::class);
        $this->action = new AuthenticateAction(
            $this->apiKeyService->reveal(),
            $this->jwtService->reveal(),
            Translator::factory([])
        );
    }

    /**
     * @test
     */
    public function notProvidingAuthDataReturnsError()
    {
        $resp = $this->action->process(ServerRequestFactory::fromGlobals(), TestUtils::createDelegateMock()->reveal());
        $this->assertEquals(400, $resp->getStatusCode());
    }

    /**
     * @test
     */
    public function properApiKeyReturnsTokenInResponse()
    {
        $this->apiKeyService->getByKey('foo')->willReturn((new ApiKey())->setId(5))
                                             ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'apiKey' => 'foo',
        ]);
        $response = $this->action->process($request, TestUtils::createDelegateMock()->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $this->assertTrue(strpos($response->getBody()->getContents(), '"token"') > 0);
    }

    /**
     * @test
     */
    public function invalidApiKeyReturnsErrorResponse()
    {
        $this->apiKeyService->getByKey('foo')->willReturn((new ApiKey())->setEnabled(false))
                                             ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'apiKey' => 'foo',
        ]);
        $response = $this->action->process($request, TestUtils::createDelegateMock()->reveal());
        $this->assertEquals(401, $response->getStatusCode());
    }
}

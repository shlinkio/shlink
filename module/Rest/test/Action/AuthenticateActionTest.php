<?php
namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Action\AuthenticateAction;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Zend\Diactoros\Response;
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

    public function setUp()
    {
        $this->apiKeyService = $this->prophesize(ApiKeyService::class);
        $this->action = new AuthenticateAction($this->apiKeyService->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function notProvidingAuthDataReturnsError()
    {
        $resp = $this->action->__invoke(ServerRequestFactory::fromGlobals(), new Response());
        $this->assertEquals(400, $resp->getStatusCode());
    }

    /**
     * @test
     */
    public function properApiKeyReturnsTokenInResponse()
    {
        $this->apiKeyService->check('foo')->willReturn(true)
                                          ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'apiKey' => 'foo',
        ]);
        $response = $this->action->__invoke($request, new Response());
        $this->assertEquals(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $this->assertTrue(strpos($response->getBody()->getContents(), '"token"') > 0);
    }

    /**
     * @test
     */
    public function invalidApiKeyReturnsErrorResponse()
    {
        $this->apiKeyService->check('foo')->willReturn(false)
                                          ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'apiKey' => 'foo',
        ]);
        $response = $this->action->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
    }
}

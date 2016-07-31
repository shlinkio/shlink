<?php
namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\RestToken;
use Shlinkio\Shlink\Rest\Action\AuthenticateAction;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;
use Shlinkio\Shlink\Rest\Service\RestTokenService;
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
    protected $tokenService;

    public function setUp()
    {
        $this->tokenService = $this->prophesize(RestTokenService::class);
        $this->action = new AuthenticateAction($this->tokenService->reveal(), Translator::factory([]));
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
    public function properCredentialsReturnTokenInResponse()
    {
        $this->tokenService->createToken('foo', 'bar')->willReturn(
            (new RestToken())->setToken('abc-ABC')
        )->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $response = $this->action->__invoke($request, new Response());
        $this->assertEquals(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $this->assertEquals(['token' => 'abc-ABC'], json_decode($response->getBody()->getContents(), true));
    }

    /**
     * @test
     */
    public function authenticationExceptionsReturnErrorResponse()
    {
        $this->tokenService->createToken('foo', 'bar')->willThrow(new AuthenticationException())
                                                      ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $response = $this->action->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
    }
}

<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Action\AuthenticateAction;
use Shlinkio\Shlink\Rest\Authentication\JWTService;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Zend\Diactoros\ServerRequest;
use function strpos;

class AuthenticateActionTest extends TestCase
{
    /** @var AuthenticateAction */
    private $action;
    /** @var ObjectProphecy */
    private $apiKeyService;
    /** @var ObjectProphecy */
    private $jwtService;

    public function setUp()
    {
        $this->apiKeyService = $this->prophesize(ApiKeyService::class);
        $this->jwtService = $this->prophesize(JWTService::class);
        $this->jwtService->create(Argument::cetera())->willReturn('');

        $this->action = new AuthenticateAction($this->apiKeyService->reveal(), $this->jwtService->reveal());
    }

    /**
     * @test
     */
    public function notProvidingAuthDataReturnsError()
    {
        $resp = $this->action->handle(new ServerRequest());
        $this->assertEquals(400, $resp->getStatusCode());
    }

    /**
     * @test
     */
    public function properApiKeyReturnsTokenInResponse()
    {
        $this->apiKeyService->getByKey('foo')->willReturn((new ApiKey())->setId('5'))
                                             ->shouldBeCalledOnce();

        $request = (new ServerRequest())->withParsedBody([
            'apiKey' => 'foo',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $this->assertTrue(strpos($response->getBody()->getContents(), '"token"') > 0);
    }

    /**
     * @test
     */
    public function invalidApiKeyReturnsErrorResponse()
    {
        $this->apiKeyService->getByKey('foo')->willReturn((new ApiKey())->disable())
                                             ->shouldBeCalledOnce();

        $request = (new ServerRequest())->withParsedBody([
            'apiKey' => 'foo',
        ]);
        $response = $this->action->handle($request);
        $this->assertEquals(401, $response->getStatusCode());
    }
}

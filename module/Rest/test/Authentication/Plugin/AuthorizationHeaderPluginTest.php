<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Authentication\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Authentication\JWTServiceInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\AuthorizationHeaderPlugin;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use function sprintf;

class AuthorizationHeaderPluginTest extends TestCase
{
    /** @var AuthorizationHeaderPlugin */
    private $plugin;
    /** @var ObjectProphecy */
    private $jwtService;

    public function setUp(): void
    {
        $this->jwtService = $this->prophesize(JWTServiceInterface::class);
        $this->plugin = new AuthorizationHeaderPlugin($this->jwtService->reveal());
    }

    /**
     * @test
     */
    public function verifyAnAuthorizationWithoutBearerTypeThrowsException()
    {
        $authToken = 'ABC-abc';
        $request = (new ServerRequest())->withHeader(
            AuthorizationHeaderPlugin::HEADER_NAME,
            $authToken
        );

        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage(sprintf(
            'You need to provide the Bearer type in the %s header.',
            AuthorizationHeaderPlugin::HEADER_NAME
        ));

        $this->plugin->verify($request);
    }

    /**
     * @test
     */
    public function verifyAnAuthorizationWithWrongTypeThrowsException()
    {
        $authToken = 'Basic ABC-abc';
        $request = (new ServerRequest())->withHeader(
            AuthorizationHeaderPlugin::HEADER_NAME,
            $authToken
        );

        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage(
            'Provided authorization type Basic is not supported. Use Bearer instead.'
        );

        $this->plugin->verify($request);
    }

    /**
     * @test
     */
    public function verifyAnExpiredTokenThrowsException()
    {
        $authToken = 'Bearer ABC-abc';
        $request = (new ServerRequest())->withHeader(
            AuthorizationHeaderPlugin::HEADER_NAME,
            $authToken
        );
        $jwtVerify = $this->jwtService->verify('ABC-abc')->willReturn(false);

        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage(sprintf(
            'Missing or invalid auth token provided. Perform a new authentication request and send provided '
            . 'token on every new request on the %s header',
            AuthorizationHeaderPlugin::HEADER_NAME
        ));

        $this->plugin->verify($request);

        $jwtVerify->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function verifyValidTokenDoesNotThrowException()
    {
        $authToken = 'Bearer ABC-abc';
        $request = (new ServerRequest())->withHeader(
            AuthorizationHeaderPlugin::HEADER_NAME,
            $authToken
        );
        $jwtVerify = $this->jwtService->verify('ABC-abc')->willReturn(true);

        $this->plugin->verify($request);

        $jwtVerify->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function updateReturnsAnUpdatedResponseWithNewJwt()
    {
        $authToken = 'Bearer ABC-abc';
        $request = (new ServerRequest())->withHeader(
            AuthorizationHeaderPlugin::HEADER_NAME,
            $authToken
        );
        $jwtRefresh = $this->jwtService->refresh('ABC-abc')->willReturn('DEF-def');

        $response = $this->plugin->update($request, new Response());

        $this->assertTrue($response->hasHeader(AuthorizationHeaderPlugin::HEADER_NAME));
        $this->assertEquals('Bearer DEF-def', $response->getHeaderLine(AuthorizationHeaderPlugin::HEADER_NAME));
        $jwtRefresh->shouldHaveBeenCalledOnce();
    }
}

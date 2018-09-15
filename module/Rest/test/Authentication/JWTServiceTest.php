<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Authentication;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Rest\Authentication\JWTService;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class JWTServiceTest extends TestCase
{
    /**
     * @var JWTService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new JWTService(new AppOptions([
            'name' => 'ShlinkTest',
            'version' => '10000.3.1',
            'secret_key' => 'foo',
        ]));
    }

    /**
     * @test
     */
    public function tokenIsProperlyCreated()
    {
        $id = '34';
        $token = $this->service->create((new ApiKey())->setId($id));
        $payload = (array) JWT::decode($token, 'foo', [JWTService::DEFAULT_ENCRYPTION_ALG]);
        $this->assertGreaterThanOrEqual($payload['iat'], time());
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertEquals($id, $payload['key']);
        $this->assertEquals('auth', $payload['sub']);
        $this->assertEquals('ShlinkTest:v10000.3.1', $payload['iss']);
    }

    /**
     * @test
     */
    public function refreshIncreasesExpiration()
    {
        $originalLifetime = 10;
        $newLifetime = 30;
        $originalPayload = ['exp' => time() + $originalLifetime];
        $token = JWT::encode($originalPayload, 'foo');
        $newToken = $this->service->refresh($token, $newLifetime);
        $newPayload = (array) JWT::decode($newToken, 'foo', [JWTService::DEFAULT_ENCRYPTION_ALG]);

        $this->assertGreaterThan($originalPayload['exp'], $newPayload['exp']);
    }

    /**
     * @test
     */
    public function verifyReturnsTrueWhenTheTokenIsCorrect()
    {
        $this->assertTrue($this->service->verify(JWT::encode([], 'foo')));
    }

    /**
     * @test
     */
    public function verifyReturnsFalseWhenTheTokenIsCorrect()
    {
        $this->assertFalse($this->service->verify('invalidToken'));
    }

    /**
     * @test
     */
    public function getPayloadWorksWithCorrectTokens()
    {
        $originalPayload = [
            'exp' => time() + 10,
            'sub' => 'testing',
        ];
        $token = JWT::encode($originalPayload, 'foo');
        $this->assertEquals($originalPayload, $this->service->getPayload($token));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Rest\Exception\AuthenticationException
     */
    public function getPayloadThrowsExceptionWithIncorrectTokens()
    {
        $this->service->getPayload('invalidToken');
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Shlinkio\Shlink\Common\Mercure\JwtProviderInterface;
use Shlinkio\Shlink\Rest\Action\MercureInfoAction;
use Shlinkio\Shlink\Rest\Exception\MercureException;

class MercureInfoActionTest extends TestCase
{
    private ObjectProphecy $provider;

    public function setUp(): void
    {
        $this->provider = $this->prophesize(JwtProviderInterface::class);
    }

    /**
     * @test
     * @dataProvider provideNoHostConfigs
     */
    public function throwsExceptionWhenConfigDoesNotHavePublicHost(array $mercureConfig): void
    {
        $buildToken = $this->provider->buildSubscriptionToken(Argument::any())->willReturn('abc.123');

        $action = new MercureInfoAction($this->provider->reveal(), $mercureConfig);

        $this->expectException(MercureException::class);
        $buildToken->shouldNotBeCalled();

        $action->handle(ServerRequestFactory::fromGlobals());
    }

    public function provideNoHostConfigs(): iterable
    {
        yield 'host not defined' => [[]];
        yield 'host is null' => [['public_hub_url' => null]];
    }

    /**
     * @test
     * @dataProvider provideValidConfigs
     */
    public function throwsExceptionWhenBuildingTokenFails(array $mercureConfig): void
    {
        $buildToken = $this->provider->buildSubscriptionToken(Argument::any())->willThrow(
            new RuntimeException('Error'),
        );

        $action = new MercureInfoAction($this->provider->reveal(), $mercureConfig);

        $this->expectException(MercureException::class);
        $buildToken->shouldBeCalledOnce();

        $action->handle(ServerRequestFactory::fromGlobals());
    }

    public function provideValidConfigs(): iterable
    {
        yield 'days not defined' => [['public_hub_url' => 'http://foobar.com']];
        yield 'days defined' => [['public_hub_url' => 'http://foobar.com', 'jwt_days_duration' => 20]];
    }

    /**
     * @test
     * @dataProvider provideDays
     */
    public function returnsExpectedInfoWhenEverythingIsOk(?int $days): void
    {
        $buildToken = $this->provider->buildSubscriptionToken(Argument::any())->willReturn('abc.123');

        $action = new MercureInfoAction($this->provider->reveal(), [
            'public_hub_url' => 'http://foobar.com',
            'jwt_days_duration' => $days,
        ]);

        /** @var JsonResponse $resp */
        $resp = $action->handle(ServerRequestFactory::fromGlobals());
        $payload = $resp->getPayload();

        $this->assertArrayHasKey('mercureHubUrl', $payload);
        $this->assertEquals('http://foobar.com/.well-known/mercure', $payload['mercureHubUrl']);
        $this->assertArrayHasKey('token', $payload);
        $this->assertArrayHasKey('jwtExpiration', $payload);
        $this->assertEquals(
            Chronos::now()->addDays($days ?? 3)->startOfDay(),
            Chronos::parse($payload['jwtExpiration'])->startOfDay(),
        );
        $buildToken->shouldHaveBeenCalledOnce();
    }

    public function provideDays(): iterable
    {
        yield 'days not defined' => [null];
        yield 'days defined' => [10];
    }
}

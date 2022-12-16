<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Mercure\JwtProviderInterface;
use Shlinkio\Shlink\Rest\Action\MercureInfoAction;
use Shlinkio\Shlink\Rest\Exception\MercureException;

class MercureInfoActionTest extends TestCase
{
    private MockObject & JwtProviderInterface $provider;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(JwtProviderInterface::class);
    }

    /**
     * @test
     * @dataProvider provideNoHostConfigs
     */
    public function throwsExceptionWhenConfigDoesNotHavePublicHost(array $mercureConfig): void
    {
        $this->provider->expects($this->never())->method('buildSubscriptionToken');

        $action = new MercureInfoAction($this->provider, $mercureConfig);

        $this->expectException(MercureException::class);

        $action->handle(ServerRequestFactory::fromGlobals());
    }

    public function provideNoHostConfigs(): iterable
    {
        yield 'host not defined' => [[]];
        yield 'host is null' => [['public_hub_url' => null]];
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
        $this->provider->expects($this->once())->method('buildSubscriptionToken')->willReturn('abc.123');

        $action = new MercureInfoAction($this->provider, [
            'public_hub_url' => 'http://foobar.com',
            'jwt_days_duration' => $days,
        ]);

        /** @var JsonResponse $resp */
        $resp = $action->handle(ServerRequestFactory::fromGlobals());
        $payload = $resp->getPayload();

        self::assertArrayHasKey('mercureHubUrl', $payload);
        self::assertEquals('http://foobar.com/.well-known/mercure', $payload['mercureHubUrl']);
        self::assertArrayHasKey('token', $payload);
        self::assertArrayHasKey('jwtExpiration', $payload);
        self::assertEquals(
            Chronos::now()->addDays($days ?? 1)->startOfDay(),
            Chronos::parse($payload['jwtExpiration'])->startOfDay(),
        );
    }

    public function provideDays(): iterable
    {
        yield 'days not defined' => [null];
        yield 'days defined' => [10];
    }
}

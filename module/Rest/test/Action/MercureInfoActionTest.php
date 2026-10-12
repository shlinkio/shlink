<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Mercure\JwtProviderInterface;
use Shlinkio\Shlink\Common\Mercure\MercureOptions;
use Shlinkio\Shlink\Common\Mercure\MercureVersion;
use Shlinkio\Shlink\Rest\Action\MercureInfoAction;
use Shlinkio\Shlink\Rest\Exception\MercureException;

class MercureInfoActionTest extends TestCase
{
    private MockObject&JwtProviderInterface $provider;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(JwtProviderInterface::class);
    }

    #[Test]
    public function throwsExceptionWhenConfigDoesNotHavePublicHost(): void
    {
        $this->provider->expects($this->never())->method('buildSubscriptionToken');

        $action = new MercureInfoAction($this->provider, new MercureOptions());

        $this->expectException(MercureException::class);

        $action->handle(ServerRequestFactory::fromGlobals());
    }

    #[Test]
    #[TestWith([MercureVersion::LEGACY])]
    #[TestWith([MercureVersion::STABLE])]
    public function returnsExpectedInfoWhenEverythingIsOk(MercureVersion $version): void
    {
        $this->provider->expects($this->once())->method('buildSubscriptionToken')->willReturn('abc.123');

        $action = new MercureInfoAction($this->provider, new MercureOptions(
            publicHubUrl: 'http://foobar.com',
            version: $version,
        ));

        /** @var JsonResponse $resp */
        $resp = $action->handle(ServerRequestFactory::fromGlobals());
        $payload = $resp->getPayload();

        self::assertArrayHasKey('mercureHubUrl', $payload);
        self::assertEquals('http://foobar.com/.well-known/mercure', $payload['mercureHubUrl']);
        self::assertArrayHasKey('token', $payload);
        self::assertArrayHasKey('jwtExpiration', $payload);
        self::assertEquals(
            Chronos::now()->addDays(1)->startOfDay(),
            Chronos::parse($payload['jwtExpiration'])->startOfDay(),
        );
        self::assertEquals($version->value, $payload['version']);
    }

    #[Test, AllowMockObjectsWithoutExpectations]
    public function getRouteDefReturnsExpectedData(): void
    {
        self::assertEquals(
            [
                'name' => MercureInfoAction::class,
                'middleware' => [MercureInfoAction::class],
                'path' => '/mercure-info',
                'allowed_methods' => ['GET'],
            ],
            MercureInfoAction::getRouteDef(),
        );
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Laminas\Diactoros\Response\RedirectResponse;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\RedirectOptions;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelper;

class RedirectResponseHelperTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideRedirectConfigs
     */
    public function expectedStatusCodeAndCacheIsReturnedBasedOnConfig(
        int $configuredStatus,
        int $configuredLifetime,
        int $expectedStatus,
        ?string $expectedCacheControl,
    ): void {
        $options = new RedirectOptions($configuredStatus, $configuredLifetime);

        $response = $this->helper($options)->buildRedirectResponse('destination');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals($expectedStatus, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals('destination', $response->getHeaderLine('Location'));
        self::assertEquals($expectedCacheControl !== null, $response->hasHeader('Cache-Control'));
        self::assertEquals($expectedCacheControl ?? '', $response->getHeaderLine('Cache-Control'));
    }

    public function provideRedirectConfigs(): iterable
    {
        yield 'status 302' => [302, 20, 302, null];
        yield 'status 307' => [307, 20, 307, null];
        yield 'status over 308' => [400, 20, 302, null];
        yield 'status below 301' => [201, 20, 302, null];
        yield 'status 301 with valid expiration' => [301, 20, 301, 'private,max-age=20'];
        yield 'status 301 with zero expiration' => [301, 0, 301, 'private,max-age=30'];
        yield 'status 301 with negative expiration' => [301, -20, 301, 'private,max-age=30'];
        yield 'status 308 with valid expiration' => [308, 20, 308, 'private,max-age=20'];
        yield 'status 308 with zero expiration' => [308, 0, 308, 'private,max-age=30'];
        yield 'status 308 with negative expiration' => [308, -20, 308, 'private,max-age=30'];
    }

    private function helper(?RedirectOptions $options = null): RedirectResponseHelper
    {
        return new RedirectResponseHelper($options ?? new RedirectOptions());
    }
}

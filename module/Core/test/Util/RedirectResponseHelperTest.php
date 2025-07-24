<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Laminas\Diactoros\Response\RedirectResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\RedirectOptions;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelper;

class RedirectResponseHelperTest extends TestCase
{
    #[Test, DataProvider('provideRedirectConfigs')]
    public function expectedStatusCodeAndCacheIsReturnedBasedOnConfig(
        RedirectOptions $options,
        int $expectedStatus,
        string|null $expectedCacheControl,
    ): void {
        $response = $this->helper($options)->buildRedirectResponse('destination');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals($expectedStatus, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals('destination', $response->getHeaderLine('Location'));
        self::assertEquals($expectedCacheControl !== null, $response->hasHeader('Cache-Control'));
        self::assertEquals($expectedCacheControl ?? '', $response->getHeaderLine('Cache-Control'));
    }

    public static function provideRedirectConfigs(): iterable
    {
        yield 'status 302' => [new RedirectOptions(302, 20), 302, null];
        yield 'status 307' => [new RedirectOptions(307, 20), 307, null];
        yield 'status over 308' => [new RedirectOptions(400, 20), 302, null];
        yield 'status below 301' => [new RedirectOptions(201, 20), 302, null];
        yield 'status 301 with valid expiration' => [new RedirectOptions(301, 20), 301, 'private,max-age=20'];
        yield 'status 301 with zero expiration' => [new RedirectOptions(301, 0), 301, 'private,max-age=30'];
        yield 'status 301 with negative expiration' => [new RedirectOptions(301, -20), 301, 'private,max-age=30'];
        yield 'status 308 with valid expiration' => [new RedirectOptions(308, 20), 308, 'private,max-age=20'];
        yield 'status 308 with zero expiration' => [new RedirectOptions(308, 0), 308, 'private,max-age=30'];
        yield 'status 308 with negative expiration' => [new RedirectOptions(308, -20), 308, 'private,max-age=30'];
        yield 'status 301 with public cache' => [
            new RedirectOptions(301, redirectCacheVisibility: 'public'),
            301,
            'public,max-age=30',
        ];
        yield 'status 308 with public cache' => [
            new RedirectOptions(308, redirectCacheVisibility: 'public'),
            308,
            'public,max-age=30',
        ];
        yield 'status 301 with private cache' => [
            new RedirectOptions(301, redirectCacheVisibility: 'private'),
            301,
            'private,max-age=30',
        ];
        yield 'status 301 with invalid cache' => [
            new RedirectOptions(301, redirectCacheVisibility: 'something-else'),
            301,
            'private,max-age=30',
        ];
    }

    private function helper(RedirectOptions|null $options = null): RedirectResponseHelper
    {
        return new RedirectResponseHelper($options ?? new RedirectOptions());
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain\Request;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Rest\Action\Domain\Request\DomainRedirectsRequest;

class DomainRedirectsRequestTest extends TestCase
{
    #[Test, DataProvider('provideInvalidData')]
    public function throwsExceptionWhenCreatingWithInvalidData(array $data): void
    {
        $this->expectException(ValidationException::class);
        DomainRedirectsRequest::fromRawData($data);
    }

    public static function provideInvalidData(): iterable
    {
        yield 'missing domain' => [[]];
        yield 'invalid domain' => [['domain' => 'foo:bar:baz']];
    }

    #[Test, DataProvider('provideValidData')]
    public function isProperlyCastToNotFoundRedirects(
        array $data,
        NotFoundRedirectConfigInterface|null $defaults,
        string $expectedAuthority,
        string|null $expectedBaseUrlRedirect,
        string|null $expectedRegular404Redirect,
        string|null $expectedInvalidShortUrlRedirect,
        string|null $expectedExpiredShortUrlRedirect,
    ): void {
        $request = DomainRedirectsRequest::fromRawData($data);
        $notFound = $request->toNotFoundRedirects($defaults);

        self::assertEquals($expectedAuthority, $request->authority());
        self::assertEquals($expectedBaseUrlRedirect, $notFound->baseUrlRedirect);
        self::assertEquals($expectedRegular404Redirect, $notFound->regular404Redirect);
        self::assertEquals($expectedInvalidShortUrlRedirect, $notFound->invalidShortUrlRedirect);
        self::assertEquals($expectedExpiredShortUrlRedirect, $notFound->expiredShortUrlRedirect);
    }

    public static function provideValidData(): iterable
    {
        yield 'no values' => [['domain' => 'foo'], null, 'foo', null, null, null, null];
        yield 'some values' => [
            ['domain' => 'foo', 'regular404Redirect' => 'bar'],
            null,
            'foo',
            null,
            'bar',
            null,
            null,
        ];
        yield 'fallbacks' => [
            ['domain' => 'domain', 'baseUrlRedirect' => 'bar'],
            new NotFoundRedirectOptions(
                invalidShortUrlRedirect: 'fallback2',
                regular404Redirect: 'fallback',
                expiredShortUrlRedirect: 'fallback3',
            ),
            'domain',
            'bar',
            'fallback',
            'fallback2',
            'fallback3',
        ];
        yield 'fallback ignored' => [
            [
                'domain' => 'domain',
                'regular404Redirect' => 'bar',
                'invalidShortUrlRedirect' => null,
                'expiredShortUrlRedirect' => null,
            ],
            new NotFoundRedirectOptions(
                invalidShortUrlRedirect: 'fallback2',
                regular404Redirect: 'fallback',
                expiredShortUrlRedirect: 'fallback3',
            ),
            'domain',
            null,
            'bar',
            null,
            null,
        ];
    }
}

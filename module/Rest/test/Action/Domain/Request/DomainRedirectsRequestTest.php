<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain\Request;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Rest\Action\Domain\Request\DomainRedirectsRequest;

class DomainRedirectsRequestTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideInvalidData
     */
    public function throwsExceptionWhenCreatingWithInvalidData(array $data): void
    {
        $this->expectException(ValidationException::class);
        DomainRedirectsRequest::fromRawData($data);
    }

    public function provideInvalidData(): iterable
    {
        yield 'missing domain' => [[]];
        yield 'invalid domain' => [['domain' => 'foo:bar:baz']];
    }

    /**
     * @test
     * @dataProvider provideValidData
     */
    public function isProperlyCastToNotFoundRedirects(
        array $data,
        ?NotFoundRedirectConfigInterface $defaults,
        string $expectedAuthority,
        ?string $expectedBaseUrlRedirect,
        ?string $expectedRegular404Redirect,
        ?string $expectedInvalidShortUrlRedirect,
    ): void {
        $request = DomainRedirectsRequest::fromRawData($data);
        $notFound = $request->toNotFoundRedirects($defaults);

        self::assertEquals($expectedAuthority, $request->authority());
        self::assertEquals($expectedBaseUrlRedirect, $notFound->baseUrlRedirect());
        self::assertEquals($expectedRegular404Redirect, $notFound->regular404Redirect());
        self::assertEquals($expectedInvalidShortUrlRedirect, $notFound->invalidShortUrlRedirect());
    }

    public function provideValidData(): iterable
    {
        yield 'no values' => [['domain' => 'foo'], null, 'foo', null, null, null];
        yield 'some values' => [['domain' => 'foo', 'regular404Redirect' => 'bar'], null, 'foo', null, 'bar', null];
        yield 'fallbacks' => [
            ['domain' => 'domain', 'baseUrlRedirect' => 'bar'],
            new NotFoundRedirectOptions(['regular404' => 'fallback', 'invalidShortUrl' => 'fallback2']),
            'domain',
            'bar',
            'fallback',
            'fallback2',
        ];
        yield 'fallback ignored' => [
            ['domain' => 'domain', 'regular404Redirect' => 'bar', 'invalidShortUrlRedirect' => null],
            new NotFoundRedirectOptions(['regular404' => 'fallback', 'invalidShortUrl' => 'fallback2']),
            'domain',
            null,
            'bar',
            null,
        ];
    }
}

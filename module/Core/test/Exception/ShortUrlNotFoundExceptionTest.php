<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;

class ShortUrlNotFoundExceptionTest extends TestCase
{
    #[Test, DataProvider('provideMessages')]
    public function properlyCreatesExceptionFromNotFoundShortCode(
        string $expectedMessage,
        string $shortCode,
        string|null $domain,
    ): void {
        $expectedAdditional = ['shortCode' => $shortCode];
        if ($domain !== null) {
            $expectedAdditional['domain'] = $domain;
        }

        $e = ShortUrlNotFoundException::fromNotFound(ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain));

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Short URL not found', $e->getTitle());
        self::assertEquals('https://shlink.io/api/error/short-url-not-found', $e->getType());
        self::assertEquals(404, $e->getStatus());
        self::assertEquals($expectedAdditional, $e->getAdditionalData());
    }

    public static function provideMessages(): iterable
    {
        yield 'without domain' => [
            'No URL found with short code "abc123"',
            'abc123',
            null,
        ];
        yield 'with domain' => [
            'No URL found with short code "bar" for domain "foo"',
            'bar',
            'foo',
        ];
    }
}

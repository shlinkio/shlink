<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;

class NonUniqueSlugExceptionTest extends TestCase
{
    #[Test, DataProvider('provideMessages')]
    public function properlyCreatesExceptionFromSlug(string $expectedMessage, string $slug, ?string $domain): void
    {
        $expectedAdditional = ['customSlug' => $slug];
        if ($domain !== null) {
            $expectedAdditional['domain'] = $domain;
        }

        $e = NonUniqueSlugException::fromSlug($slug, $domain);

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Invalid custom slug', $e->getTitle());
        self::assertEquals('https://shlink.io/api/error/non-unique-slug', $e->getType());
        self::assertEquals(400, $e->getStatus());
        self::assertEquals($expectedAdditional, $e->getAdditionalData());
    }

    public static function provideMessages(): iterable
    {
        yield 'without domain' => [
            'Provided slug "foo" is already in use.',
            'foo',
            null,
        ];
        yield 'with domain' => [
            'Provided slug "baz" is already in use for domain "bar".',
            'baz',
            'bar',
        ];
    }
}

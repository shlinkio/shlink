<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;

class NonUniqueSlugExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMessages
     */
    public function properlyCreatesExceptionFromSlug(string $expectedMessage, string $slug, ?string $domain): void
    {
        $expectedAdditional = ['customSlug' => $slug];
        if ($domain !== null) {
            $expectedAdditional['domain'] = $domain;
        }

        $e = NonUniqueSlugException::fromSlug($slug, $domain);

        $this->assertEquals($expectedMessage, $e->getMessage());
        $this->assertEquals($expectedMessage, $e->getDetail());
        $this->assertEquals('Invalid custom slug', $e->getTitle());
        $this->assertEquals('INVALID_SLUG', $e->getType());
        $this->assertEquals(400, $e->getStatus());
        $this->assertEquals($expectedAdditional, $e->getAdditionalData());
    }

    public function provideMessages(): iterable
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

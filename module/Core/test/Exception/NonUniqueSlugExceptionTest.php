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
        $e = NonUniqueSlugException::fromSlug($slug, $domain);
        $this->assertEquals($expectedMessage, $e->getMessage());
    }

    public function provideMessages(): iterable
    {
        yield 'without domain' => [
            'Provided slug "foo" is not unique.',
            'foo',
            null,
        ];
        yield 'with domain' => [
            'Provided slug "baz" is not unique for domain "bar".',
            'baz',
            'bar',
        ];
    }
}

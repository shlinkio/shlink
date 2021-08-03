<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\InvalidDomainException;

class InvalidDomainExceptionTest extends TestCase
{
    /** @test */
    public function configuresTheExceptionAsExpected(): void
    {
        $e = InvalidDomainException::forDefaultDomainRedirects();
        $expected = 'You cannot configure default domain\'s redirects this way. Use the configuration or env vars.';

        self::assertEquals($expected, $e->getMessage());
        self::assertEquals($expected, $e->getDetail());
        self::assertEquals('Invalid domain', $e->getTitle());
        self::assertEquals('INVALID_DOMAIN', $e->getType());
        self::assertEquals(403, $e->getStatus());
    }
}

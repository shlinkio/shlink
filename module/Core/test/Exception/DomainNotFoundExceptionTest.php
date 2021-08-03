<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;

use function sprintf;

class DomainNotFoundExceptionTest extends TestCase
{
    /** @test */
    public function properlyCreatesExceptionFromId(): void
    {
        $id = '123';
        $expectedMessage = sprintf('Domain with id "%s" could not be found', $id);
        $e = DomainNotFoundException::fromId($id);

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Domain not found', $e->getTitle());
        self::assertEquals('DOMAIN_NOT_FOUND', $e->getType());
        self::assertEquals(['id' => $id], $e->getAdditionalData());
        self::assertEquals(404, $e->getStatus());
    }

    /** @test */
    public function properlyCreatesExceptionFromAuthority(): void
    {
        $authority = 'example.com';
        $expectedMessage = sprintf('Domain with authority "%s" could not be found', $authority);
        $e = DomainNotFoundException::fromAuthority($authority);

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Domain not found', $e->getTitle());
        self::assertEquals('DOMAIN_NOT_FOUND', $e->getType());
        self::assertEquals(['authority' => $authority], $e->getAdditionalData());
        self::assertEquals(404, $e->getStatus());
    }
}

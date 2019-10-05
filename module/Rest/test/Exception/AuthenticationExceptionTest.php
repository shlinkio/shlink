<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;
use Throwable;

class AuthenticationExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePrev
     */
    public function expiredJWTCreatesExpectedException(?Throwable $prev): void
    {
        $e = AuthenticationException::expiredJWT($prev);

        $this->assertEquals($prev, $e->getPrevious());
        $this->assertEquals(-1, $e->getCode());
        $this->assertEquals('The token has expired.', $e->getMessage());
    }

    public function providePrev(): iterable
    {
        yield 'with previous exception' => [new Exception('Prev')];
        yield 'without previous exception' => [null];
    }
}

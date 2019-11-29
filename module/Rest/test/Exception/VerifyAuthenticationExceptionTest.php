<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;

class VerifyAuthenticationExceptionTest extends TestCase
{
    /** @test */
    public function createsExpectedExceptionForInvalidApiKey(): void
    {
        $e = VerifyAuthenticationException::forInvalidApiKey();

        $this->assertEquals('Provided API key does not exist or is invalid.', $e->getMessage());
    }
}

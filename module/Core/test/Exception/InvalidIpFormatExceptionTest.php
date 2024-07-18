<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\InvalidIpFormatException;

class InvalidIpFormatExceptionTest extends TestCase
{
    #[Test]
    public function fromInvalidIp(): void
    {
        $e = InvalidIpFormatException::fromInvalidIp('invalid');
        self::assertEquals('Provided IP invalid does not have the right format. Expected X.X.X.X', $e->getMessage());
    }
}

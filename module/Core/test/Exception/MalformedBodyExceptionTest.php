<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\MalformedBodyException;

class MalformedBodyExceptionTest extends TestCase
{
    #[Test]
    public function createsExpectedException(): void
    {
        $prev = new JsonException();
        $e = MalformedBodyException::forInvalidJson($prev);

        self::assertEquals($prev, $e->getPrevious());
        self::assertEquals('Provided request does not contain a valid JSON body.', $e->getMessage());
        self::assertEquals('Provided request does not contain a valid JSON body.', $e->getDetail());
        self::assertEquals('Malformed request body', $e->getTitle());
        self::assertEquals('https://shlink.io/api/error/malformed-request-body', $e->getType());
        self::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getStatus());
    }
}

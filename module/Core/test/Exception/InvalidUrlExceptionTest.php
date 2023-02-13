<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Throwable;

use function sprintf;

class InvalidUrlExceptionTest extends TestCase
{
    #[Test, DataProvider('providePrevious')]
    public function properlyCreatesExceptionFromUrl(?Throwable $prev): void
    {
        $url = 'http://the_url.com';
        $expectedMessage = sprintf('Provided URL %s is invalid. Try with a different one.', $url);
        $e = InvalidUrlException::fromUrl($url, $prev);

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Invalid URL', $e->getTitle());
        self::assertEquals('https://shlink.io/api/error/invalid-url', $e->getType());
        self::assertEquals(['url' => $url], $e->getAdditionalData());
        self::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getCode());
        self::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getStatus());
        self::assertEquals($prev, $e->getPrevious());
    }

    public static function providePrevious(): iterable
    {
        yield 'null previous' => [null];
        yield 'instance previous' => [new Exception('Previous error', 10)];
    }
}

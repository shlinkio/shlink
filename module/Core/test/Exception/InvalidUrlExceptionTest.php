<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Throwable;

use function sprintf;

class InvalidUrlExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePrevious
     */
    public function properlyCreatesExceptionFromUrl(?Throwable $prev): void
    {
        $url = 'http://the_url.com';
        $expectedMessage = sprintf('Provided URL %s is invalid. Try with a different one.', $url);
        $e = InvalidUrlException::fromUrl($url, $prev);

        $this->assertEquals($expectedMessage, $e->getMessage());
        $this->assertEquals($expectedMessage, $e->getDetail());
        $this->assertEquals('Invalid URL', $e->getTitle());
        $this->assertEquals('INVALID_URL', $e->getType());
        $this->assertEquals(['url' => $url], $e->getAdditionalData());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getCode());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getStatus());
        $this->assertEquals($prev, $e->getPrevious());
    }

    public function providePrevious(): iterable
    {
        yield 'null previous' => [null];
        yield 'instance previous' => [new Exception('Previous error', 10)];
    }
}

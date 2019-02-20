<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Throwable;

class InvalidUrlExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePrevious
     */
    public function properlyCreatesExceptionFromUrl(?Throwable $prev): void
    {
        $e = InvalidUrlException::fromUrl('http://the_url.com', $prev);

        $this->assertEquals('Provided URL "http://the_url.com" is not an existing and valid URL', $e->getMessage());
        $this->assertEquals($prev !== null ? $prev->getCode() : -1, $e->getCode());
        $this->assertEquals($prev, $e->getPrevious());
    }

    public function providePrevious(): iterable
    {
        yield 'null previous' => [null];
        yield 'instance previous' => [new Exception('Previous error', 10)];
    }
}

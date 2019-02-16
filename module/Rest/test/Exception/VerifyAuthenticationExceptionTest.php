<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Throwable;
use function array_map;
use function random_int;
use function range;
use function sprintf;

class VerifyAuthenticationExceptionTest extends TestCase
{
    use StringUtilsTrait;

    /**
     * @test
     * @dataProvider provideExceptionData
     */
    public function withErrorCreatesExpectedException(string $code, string $message, ?Throwable $prev): void
    {
        $e = VerifyAuthenticationException::withError($code, $message, $prev);

        $this->assertEquals(0, $e->getCode());
        $this->assertEquals(
            sprintf('Authentication verification failed with the public message "%s"', $message),
            $e->getMessage()
        );
        $this->assertEquals($code, $e->getErrorCode());
        $this->assertEquals($message, $e->getPublicMessage());
        $this->assertEquals($prev, $e->getPrevious());
    }

    public function provideExceptionData(): iterable
    {
        return array_map(function () {
            return [
                $this->generateRandomString(),
                $this->generateRandomString(50),
                random_int(0, 1) === 1 ? new Exception('Prev') : null,
            ];
        }, range(1, 10));
    }

    /**
     * @test
     * @dataProvider provideConstructorData
     */
    public function constructCreatesExpectedException(
        string $errorCode,
        string $publicMessage,
        string $message,
        int $code,
        ?Throwable $prev
    ): void {
        $e = new VerifyAuthenticationException($errorCode, $publicMessage, $message, $code, $prev);

        $this->assertEquals($code, $e->getCode());
        $this->assertEquals($message, $e->getMessage());
        $this->assertEquals($errorCode, $e->getErrorCode());
        $this->assertEquals($publicMessage, $e->getPublicMessage());
        $this->assertEquals($prev, $e->getPrevious());
    }

    public function provideConstructorData(): iterable
    {
        return array_map(function (int $i) {
            return [
                $this->generateRandomString(),
                $this->generateRandomString(30),
                $this->generateRandomString(50),
                $i,
                random_int(0, 1) === 1 ? new Exception('Prev') : null,
            ];
        }, range(10, 20));
    }

    /** @test */
    public function defaultConstructorValuesAreKept(): void
    {
        $e = new VerifyAuthenticationException('foo', 'bar');

        $this->assertEquals(0, $e->getCode());
        $this->assertEquals('', $e->getMessage());
        $this->assertEquals('foo', $e->getErrorCode());
        $this->assertEquals('bar', $e->getPublicMessage());
        $this->assertNull($e->getPrevious());
    }
}

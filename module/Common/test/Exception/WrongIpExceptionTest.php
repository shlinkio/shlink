<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Exception\WrongIpException;

class WrongIpExceptionTest extends TestCase
{
    /** @test */
    public function fromIpAddressProperlyCreatesExceptionWithoutPrev()
    {
        $e = WrongIpException::fromIpAddress('1.2.3.4');

        $this->assertEquals('Provided IP "1.2.3.4" is invalid', $e->getMessage());
        $this->assertEquals(0, $e->getCode());
        $this->assertNull($e->getPrevious());
    }
    /** @test */
    public function fromIpAddressProperlyCreatesExceptionWithPrev()
    {
        $prev = new Exception('Previous error');
        $e = WrongIpException::fromIpAddress('1.2.3.4', $prev);

        $this->assertEquals('Provided IP "1.2.3.4" is invalid', $e->getMessage());
        $this->assertEquals(0, $e->getCode());
        $this->assertSame($prev, $e->getPrevious());
    }
}

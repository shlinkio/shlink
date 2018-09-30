<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Installer\Exception\MissingRequiredOptionException;

class MissingRequiredOptionExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function fromOptionsGeneratesExpectedMessage()
    {
        $e = MissingRequiredOptionException::fromOption('foo');
        $this->assertEquals('The "foo" is required and can\'t be empty', $e->getMessage());
    }
}

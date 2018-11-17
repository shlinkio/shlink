<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Util;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use function strlen;

class StringUtilsTraitTest extends TestCase
{
    use StringUtilsTrait;

    /**
     * @test
     * @dataProvider provideLengths
     */
    public function generateRandomStringGeneratesStringOfProvidedLength(int $length)
    {
        $this->assertEquals($length, strlen($this->generateRandomString($length)));
    }

    public function provideLengths(): array
    {
        return [
            [1],
            [10],
            [15],
        ];
    }

    /**
     * @test
     */
    public function generatesUuidV4()
    {
        $uuidPattern = '/[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}/';

        $this->assertRegExp($uuidPattern, $this->generateV4Uuid());
        $this->assertRegExp($uuidPattern, $this->generateV4Uuid());
        $this->assertRegExp($uuidPattern, $this->generateV4Uuid());
        $this->assertRegExp($uuidPattern, $this->generateV4Uuid());
        $this->assertRegExp($uuidPattern, $this->generateV4Uuid());
    }
}

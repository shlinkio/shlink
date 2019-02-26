<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\IpGeolocation;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\IpGeolocation\EmptyIpLocationResolver;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;

use function Functional\map;
use function range;

class EmptyIpLocationResolverTest extends TestCase
{
    use StringUtilsTrait;

    /** @var EmptyIpLocationResolver */
    private $resolver;

    public function setUp(): void
    {
        $this->resolver = new EmptyIpLocationResolver();
    }

    /**
     * @test
     * @dataProvider provideEmptyResponses
     */
    public function alwaysReturnsAnEmptyLocation(string $ipAddress): void
    {
        $this->assertEquals(Location::emptyInstance(), $this->resolver->resolveIpLocation($ipAddress));
    }

    public function provideEmptyResponses(): array
    {
        return map(range(0, 5), function () {
            return [$this->generateRandomString(15)];
        });
    }
}

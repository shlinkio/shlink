<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\IpGeolocation;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\IpGeolocation\EmptyIpLocationResolver;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use function Functional\map;
use function range;

class EmptyIpLocationResolverTest extends TestCase
{
    use StringUtilsTrait;

    private const EMPTY_RESP = [
        'country_code' => '',
        'country_name' => '',
        'region_name' => '',
        'city' => '',
        'latitude' => '',
        'longitude' => '',
        'time_zone' => '',
    ];

    /**
     * @var EmptyIpLocationResolver
     */
    private $resolver;

    public function setUp()
    {
        $this->resolver = new EmptyIpLocationResolver();
    }

    /**
     * @test
     * @dataProvider provideEmptyResponses
     */
    public function alwaysReturnsAnEmptyResponse(array $expected, string $ipAddress)
    {
        $this->assertEquals($expected, $this->resolver->resolveIpLocation($ipAddress));
    }

    public function provideEmptyResponses(): array
    {
        return map(range(0, 5), function () {
            return [self::EMPTY_RESP, $this->generateRandomString(10)];
        });
    }
}

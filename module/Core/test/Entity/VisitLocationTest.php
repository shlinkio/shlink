<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Core\Entity\VisitLocation;

class VisitLocationTest extends TestCase
{
    /** @test */
    public function valuesFoundWhenExchangingArrayAreCastToString(): void
    {
        $payload = new Location('', '', '', '', 1000.7, -2000.4, '');
        $location = new VisitLocation($payload);

        $this->assertSame('1000.7', $location->getLatitude());
        $this->assertSame('-2000.4', $location->getLongitude());
    }
}

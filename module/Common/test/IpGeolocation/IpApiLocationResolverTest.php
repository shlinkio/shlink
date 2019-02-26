<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\IpGeolocation;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\IpGeolocation\IpApiLocationResolver;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;

use function json_encode;

class IpApiLocationResolverTest extends TestCase
{
    /** @var IpApiLocationResolver */
    private $ipResolver;
    /** @var ObjectProphecy */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->prophesize(Client::class);
        $this->ipResolver = new IpApiLocationResolver($this->client->reveal());
    }

    /** @test */
    public function correctIpReturnsDecodedInfo(): void
    {
        $actual = [
            'countryCode' => 'bar',
            'lat' => 5,
            'lon' => 10,
        ];
        $expected = new Location('bar', '', '', '', 5, 10, '');
        $response = new Response();
        $response->getBody()->write(json_encode($actual));
        $response->getBody()->rewind();

        $this->client->get('http://ip-api.com/json/1.2.3.4')->willReturn($response)
                                                            ->shouldBeCalledOnce();
        $this->assertEquals($expected, $this->ipResolver->resolveIpLocation('1.2.3.4'));
    }

    /** @test */
    public function guzzleExceptionThrowsShlinkException(): void
    {
        $this->client->get('http://ip-api.com/json/1.2.3.4')->willThrow(new TransferException())
                                                            ->shouldBeCalledOnce();
        $this->expectException(WrongIpException::class);
        $this->ipResolver->resolveIpLocation('1.2.3.4');
    }
}

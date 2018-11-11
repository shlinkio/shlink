<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\IpGeolocation;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\IpGeolocation\IpApiLocationResolver;
use function json_encode;

class IpApiLocationResolverTest extends TestCase
{
    /**
     * @var IpApiLocationResolver
     */
    protected $ipResolver;
    /**
     * @var ObjectProphecy
     */
    protected $client;

    public function setUp()
    {
        $this->client = $this->prophesize(Client::class);
        $this->ipResolver = new IpApiLocationResolver($this->client->reveal());
    }

    /**
     * @test
     */
    public function correctIpReturnsDecodedInfo()
    {
        $actual = [
            'countryCode' => 'bar',
            'lat' => 5,
            'lon' => 10,
        ];
        $expected = [
            'country_code' => 'bar',
            'country_name' => '',
            'region_name' => '',
            'city' => '',
            'latitude' => 5,
            'longitude' => 10,
            'time_zone' => '',
        ];
        $response = new Response();
        $response->getBody()->write(json_encode($actual));
        $response->getBody()->rewind();

        $this->client->get('http://ip-api.com/json/1.2.3.4')->willReturn($response)
                                                            ->shouldBeCalledOnce();
        $this->assertEquals($expected, $this->ipResolver->resolveIpLocation('1.2.3.4'));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Common\Exception\WrongIpException
     */
    public function guzzleExceptionThrowsShlinkException()
    {
        $this->client->get('http://ip-api.com/json/1.2.3.4')->willThrow(new TransferException())
                                                            ->shouldBeCalledOnce();
        $this->ipResolver->resolveIpLocation('1.2.3.4');
    }
}

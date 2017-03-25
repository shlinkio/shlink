<?php
namespace ShlinkioTest\Shlink\Common\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Service\IpLocationResolver;

class IpLocationResolverTest extends TestCase
{
    /**
     * @var IpLocationResolver
     */
    protected $ipResolver;
    /**
     * @var ObjectProphecy
     */
    protected $client;

    public function setUp()
    {
        $this->client = $this->prophesize(Client::class);
        $this->ipResolver = new IpLocationResolver($this->client->reveal());
    }

    /**
     * @test
     */
    public function correctIpReturnsDecodedInfo()
    {
        $expected = [
            'foo' => 'bar',
            'baz' => 'foo',
        ];
        $response = new Response();
        $response->getBody()->write(json_encode($expected));
        $response->getBody()->rewind();

        $this->client->get('http://freegeoip.net/json/1.2.3.4')->willReturn($response)
                                                               ->shouldBeCalledTimes(1);
        $this->assertEquals($expected, $this->ipResolver->resolveIpLocation('1.2.3.4'));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Common\Exception\WrongIpException
     */
    public function guzzleExceptionThrowsShlinkException()
    {
        $this->client->get('http://freegeoip.net/json/1.2.3.4')->willThrow(new TransferException())
                                                               ->shouldBeCalledTimes(1);
        $this->ipResolver->resolveIpLocation('1.2.3.4');
    }
}

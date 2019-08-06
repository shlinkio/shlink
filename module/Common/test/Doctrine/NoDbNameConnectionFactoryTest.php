<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Doctrine\NoDbNameConnectionFactory;

class NoDbNameConnectionFactoryTest extends TestCase
{
    /** @var NoDbNameConnectionFactory */
    private $factory;
    /** @var ObjectProphecy */
    private $container;
    /** @var ObjectProphecy */
    private $originalConn;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->originalConn = $this->prophesize(Connection::class);
        $this->container->get(Connection::class)->willReturn($this->originalConn->reveal());

        $this->factory = new NoDbNameConnectionFactory();
    }

    /** @test */
    public function createsNewConnectionRemovingDbNameFromOriginalConnectionParams(): void
    {
        $params = [
            'username' => 'foo',
            'password' => 'bar',
            'dbname' => 'something',
        ];
        $getOriginalParams = $this->originalConn->getParams()->willReturn($params);
        $getOriginalDriver = $this->originalConn->getDriver()->willReturn($this->prophesize(Driver::class)->reveal());
        $getOriginalConfig = $this->originalConn->getConfiguration()->willReturn(null);
        $getOriginalEvents = $this->originalConn->getEventManager()->willReturn(null);

        $conn = ($this->factory)($this->container->reveal());

        $this->assertEquals([
            'username' => 'foo',
            'password' => 'bar',
        ], $conn->getParams());
        $getOriginalParams->shouldHaveBeenCalledOnce();
        $getOriginalDriver->shouldHaveBeenCalledOnce();
        $getOriginalConfig->shouldHaveBeenCalledOnce();
        $getOriginalEvents->shouldHaveBeenCalledOnce();
        $this->container->get(Connection::class)->shouldHaveBeenCalledOnce();
    }
}

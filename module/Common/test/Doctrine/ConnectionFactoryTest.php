<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Doctrine\ConnectionFactory;

class ConnectionFactoryTest extends TestCase
{
    /** @var ConnectionFactory */
    private $factory;
    /** @var ObjectProphecy */
    private $container;
    /** @var ObjectProphecy */
    private $em;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->container->get(EntityManager::class)->willReturn($this->em->reveal());

        $this->factory = new ConnectionFactory();
    }

    /** @test */
    public function properServiceFallbackOccursWhenInvoked(): void
    {
        $connection = $this->prophesize(Connection::class)->reveal();
        $getConnection = $this->em->getConnection()->willReturn($connection);

        $result = ($this->factory)($this->container->reveal());

        $this->assertSame($connection, $result);
        $getConnection->shouldHaveBeenCalledOnce();
        $this->container->get(EntityManager::class)->shouldHaveBeenCalledOnce();
    }
}

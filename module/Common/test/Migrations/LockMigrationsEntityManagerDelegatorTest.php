<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Migrations;

use Closure;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Migrations\LockMigrationsEntityManagerDelegator;
use Shlinkio\Shlink\Common\Migrations\LockMigrationsSubscriber;
use Symfony\Component\Lock\Factory as Locker;

class LockMigrationsEntityManagerDelegatorTest extends TestCase
{
    private static $originalScriptName;

    /** @var LockMigrationsEntityManagerDelegator */
    private $delegator;
    /** @var ObjectProphecy */
    private $container;
    /** @var ObjectProphecy */
    private $em;
    /** @var ObjectProphecy */
    private $connection;
    /** @var ObjectProphecy */
    private $eventManager;
    /** @var Closure */
    private $callback;

    public static function setUpBeforeClass(): void
    {
        static::$originalScriptName = $_SERVER['SCRIPT_NAME'];
    }

    public static function tearDownAfterClass(): void
    {
        $_SERVER['SCRIPT_NAME'] = static::$originalScriptName;
    }

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->connection = $this->prophesize(Connection::class);
        $this->em->getConnection()->willReturn($this->connection->reveal());
        $this->eventManager = $this->prophesize(EventManager::class);
        $this->connection->getEventManager()->willReturn($this->eventManager->reveal());

        $this->callback = function () {
            return $this->em->reveal();
        };
        $this->delegator = new LockMigrationsEntityManagerDelegator();
    }

    /** @test */
    public function subscriberIsNotRegisteredWhenScriptIsNotMigrations(): void
    {
        $_SERVER['SCRIPT_NAME'] = 'not_migrations';

        ($this->delegator)($this->container->reveal(), '', $this->callback);

        $this->container->get(Locker::class)->shouldNotHaveBeenCalled();
        $this->em->getConnection()->shouldNotHaveBeenCalled();
        $this->connection->getEventManager()->shouldNotHaveBeenCalled();
        $this->eventManager->addEventSubscriber()->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideMigrationsScripts
     */
    public function subscriberIsRegisteredWhenScriptIsMigrations(string $scriptName): void
    {
        $_SERVER['SCRIPT_NAME'] = $scriptName;

        $locker = $this->prophesize(Locker::class);
        $getLocker = $this->container->get(Locker::class)->willReturn($locker->reveal());
        $addSubscriber = $this->eventManager->addEventSubscriber(Argument::type(LockMigrationsSubscriber::class));

        ($this->delegator)($this->container->reveal(), '', $this->callback);

        $getLocker->shouldHaveBeenCalledOnce();
        $this->em->getConnection()->shouldHaveBeenCalledOnce();
        $this->connection->getEventManager()->shouldHaveBeenCalledOnce();
        $addSubscriber->shouldHaveBeenCalledOnce();
    }

    public function provideMigrationsScripts(): iterable
    {
        return [['doctrine-migrations', 'DOCTRINE-migrations', 'DOCTRINE-MIGRATIONS']];
    }
}

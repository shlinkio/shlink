<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Migrations;

use Doctrine\Migrations\Events;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\MigrationsException;
use Shlinkio\Shlink\Common\Migrations\LockMigrationsSubscriber;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Lock\LockInterface;

class LockMigrationsSubscriberTest extends TestCase
{
    /** @var LockMigrationsSubscriber */
    private $subscriber;
    /** @var ObjectProphecy */
    private $locker;

    public function setUp(): void
    {
        $this->locker = $this->prophesize(Locker::class);
        $this->subscriber = new LockMigrationsSubscriber($this->locker->reveal());
    }

    /** @test */
    public function itIsSubscribedToProperEvents(): void
    {
        $expectedEvents = [Events::onMigrationsMigrating, Events::onMigrationsMigrated];
        $this->assertEquals($expectedEvents, $this->subscriber->getSubscribedEvents());
    }

    /** @test */
    public function migrationInProgressIsNotifiedWhenLockCannotBeAcquired(): void
    {
        $lock = $this->prophesize(LockInterface::class);
        $acquire = $lock->acquire()->willReturn(false);
        $createLock = $this->locker->createLock(Argument::type('string'), Argument::type('numeric'))->willReturn(
            $lock->reveal()
        );

        $acquire->shouldBeCalledOnce();
        $createLock->shouldBeCalledOnce();

        $this->expectException(MigrationsException::class);
        $this->expectExceptionMessage('Migrations already in progress. Skipping.');

        $this->subscriber->onMigrationsMigrating();
    }

    /** @test */
    public function migrationForTheFirstTimeAcquiresLockAndReleasesLockAfterFinishing(): void
    {
        $lock = $this->prophesize(LockInterface::class);
        $acquire = $lock->acquire()->willReturn(true);
        $release = $lock->release()->will(function () {
        });
        $createLock = $this->locker->createLock(Argument::type('string'), Argument::type('numeric'))->willReturn(
            $lock->reveal()
        );

        $this->subscriber->onMigrationsMigrating();
        $this->subscriber->onMigrationsMigrated();

        $acquire->shouldHaveBeenCalledOnce();
        $release->shouldHaveBeenCalledOnce();
        $createLock->shouldHaveBeenCalledOnce();
    }
}

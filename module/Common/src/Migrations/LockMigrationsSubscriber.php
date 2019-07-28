<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Migrations;

use Doctrine\Common\EventSubscriber;
use Doctrine\Migrations\Events;
use Shlinkio\Shlink\Common\Exception\MigrationsException;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Lock\Lock;

class LockMigrationsSubscriber implements EventSubscriber
{
    private const MIGRATIONS_LOCK_NAME = 'migrations';
    private const MIGRATIONS_LOCK_TTL = 90; // 1.5 minutes

    /** @var Locker */
    private $locker;
    /** @var Lock */
    private $lock;

    public function __construct(Locker $locker)
    {
        $this->locker = $locker;
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onMigrationsMigrating, Events::onMigrationsMigrated];
    }

    public function onMigrationsMigrating(): void
    {
        $this->lock = $this->locker->createLock(self::MIGRATIONS_LOCK_NAME, self::MIGRATIONS_LOCK_TTL);
        $migrationInProgress = ! $this->lock->acquire();

        if ($migrationInProgress) {
            throw MigrationsException::migrationInProgress();
        }
    }

    public function onMigrationsMigrated(): void
    {
        $this->lock !== null && $this->lock->release();
    }
}

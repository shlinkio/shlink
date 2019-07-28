<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Exception\MigrationsException;

class MigrationsExceptionTest extends TestCase
{
    /** @test */
    public function migrationInProgressSetsExpectedMessage(): void
    {
        $e = MigrationsException::migrationInProgress();

        $this->assertEquals('Migrations already in progress. Skipping.', $e->getMessage());
    }
}

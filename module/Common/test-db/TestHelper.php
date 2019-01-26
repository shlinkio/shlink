<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common;

use Symfony\Component\Process\Process;
use function file_exists;
use function realpath;
use function sys_get_temp_dir;
use function unlink;

class TestHelper
{
    public function createTestDb(): void
    {
        $shlinkDbPath = realpath(sys_get_temp_dir()) . '/shlink-tests.db';
        if (file_exists($shlinkDbPath)) {
            unlink($shlinkDbPath);
        }

        $process = new Process(['vendor/bin/doctrine', 'orm:schema-tool:create', '--no-interaction', '-q']);
        $process->inheritEnvironmentVariables()
                ->mustRun();
    }
}

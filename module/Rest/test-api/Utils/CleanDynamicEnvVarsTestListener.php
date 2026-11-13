<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Utils;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

use function file_exists;
use function unlink;

use const ShlinkioTest\Shlink\DYNAMIC_ENV_VARS_FILE;

/**
 * Tries to delete the dynamic env vars after a test has finished, if any, so that it does not affect subsequent tests
 */
class CleanDynamicEnvVarsTestListener implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        if (file_exists(DYNAMIC_ENV_VARS_FILE)) {
            unlink(DYNAMIC_ENV_VARS_FILE);
            // Restart server again so that it removes the env vars from the file that has just been deleted
            ApiTestsExtension::restartRRServer();
        }
    }
}

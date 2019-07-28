<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Exception;

class MigrationsException extends RuntimeException
{
    public static function migrationInProgress(): self
    {
        return new self('Migrations already in progress. Skipping.');
    }
}

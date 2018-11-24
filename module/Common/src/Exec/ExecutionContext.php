<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Exec;

use const PHP_SAPI;
use function Shlinkio\Shlink\Common\env;

abstract class ExecutionContext
{
    public const WEB = 'shlink_web';
    public const CLI = 'shlink_cli';

    public static function currentContextIsSwoole(): bool
    {
        return PHP_SAPI === 'cli' && env('CURRENT_SHLINK_CONTEXT', self::WEB) === self::WEB;
    }
}

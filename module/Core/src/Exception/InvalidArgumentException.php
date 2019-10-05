<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use InvalidArgumentException as SplInvalidArgumentException;

class InvalidArgumentException extends SplInvalidArgumentException implements ExceptionInterface
{
}

<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Exception;

use InvalidArgumentException as SplInvalidArgumentException;

class InvalidArgumentException extends SplInvalidArgumentException implements ExceptionInterface
{
}

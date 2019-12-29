<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use DomainException as SplDomainException;

class DomainException extends SplDomainException implements ExceptionInterface
{
}

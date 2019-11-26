<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Util;

use Shlinkio\Shlink\Rest\Exception as Rest;

/** @deprecated */
class RestUtils
{
    public const INVALID_AUTHORIZATION_ERROR = Rest\MissingAuthenticationException::TYPE;
}

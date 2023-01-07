<?php

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\StatusCodeInterface;

use function Functional\contains;

enum RedirectStatus: int
{
    case STATUS_301 = StatusCodeInterface::STATUS_MOVED_PERMANENTLY;
    case STATUS_302 = StatusCodeInterface::STATUS_FOUND;
    case STATUS_307 = StatusCodeInterface::STATUS_TEMPORARY_REDIRECT;
    case STATUS_308 = StatusCodeInterface::STATUS_PERMANENT_REDIRECT;

    public function allowsCache(): bool
    {
        return contains([self::STATUS_301, self::STATUS_308], $this);
    }
}

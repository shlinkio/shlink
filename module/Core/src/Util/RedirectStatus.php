<?php

namespace Shlinkio\Shlink\Core\Util;

use function Functional\contains;

enum RedirectStatus: int
{
    case STATUS_301 = 301; // StatusCodeInterface::STATUS_MOVED_PERMANENTLY;
    case STATUS_302 = 302; // StatusCodeInterface::STATUS_FOUND;
    case STATUS_307 = 307; // StatusCodeInterface::STATUS_TEMPORARY_REDIRECT;
    case STATUS_308 = 308; // StatusCodeInterface::STATUS_PERMANENT_REDIRECT;

    public function allowsCache(): bool
    {
        return contains([self::STATUS_301, self::STATUS_308], $this);
    }

    public function isLegacyStatus(): bool
    {
        return contains([self::STATUS_301, self::STATUS_302], $this);
    }
}

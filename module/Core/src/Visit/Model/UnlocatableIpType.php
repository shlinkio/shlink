<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

enum UnlocatableIpType
{
    case EMPTY_ADDRESS;
    case LOCALHOST;
    case ERROR;
}

<?php

namespace Shlinkio\Shlink\Core\Visit\Model;

enum UnlocatableIpType
{
    case EMPTY_ADDRESS;
    case LOCALHOST;
    case ERROR;
}

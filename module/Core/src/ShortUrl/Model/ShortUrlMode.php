<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

enum ShortUrlMode: string
{
    case STRICT = 'strict';
    case LOOSE = 'loose';
}

<?php

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

enum ShortUrlMode: string
{
    case STRICT = 'strict';
    case LOOSE = 'loose';
}

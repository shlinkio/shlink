<?php

declare(strict_types=1);

// phpcs:disable
// TODO Enable coding style checks again once code sniffer 3.7 is released https://github.com/squizlabs/PHP_CodeSniffer/issues/3474
namespace Shlinkio\Shlink\Core\ShortUrl\Model;

enum TagsMode: string
{
    case ANY = 'any';
    case ALL = 'all';
}

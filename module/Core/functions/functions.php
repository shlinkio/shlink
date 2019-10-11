<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use PUGX\Shortid\Factory as ShortIdFactory;

function generateRandomShortCode(int $length = 5): string
{
    static $shortIdFactory;
    if ($shortIdFactory === null) {
        $shortIdFactory = new ShortIdFactory();
    }

    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return $shortIdFactory->generate($length, $alphabet)->serialize();
}

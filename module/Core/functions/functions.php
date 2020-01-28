<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Cake\Chronos\Chronos;
use DateTimeInterface;
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

function parseDateFromQuery(array $query, string $dateName): ?Chronos
{
    return ! isset($query[$dateName]) || empty($query[$dateName]) ? null : Chronos::parse($query[$dateName]);
}

/**
 * @param string|DateTimeInterface|Chronos|null $date
 */
function parseDateField($date): ?Chronos
{
    if ($date === null || $date instanceof Chronos) {
        return $date;
    }

    if ($date instanceof DateTimeInterface) {
        return Chronos::instance($date);
    }

    return Chronos::parse($date);
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use PUGX\Shortid\Factory as ShortIdFactory;

use function sprintf;

const DEFAULT_SHORT_CODES_LENGTH = 5;
const MIN_SHORT_CODES_LENGTH = 4;

function generateRandomShortCode(int $length): string
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

function determineTableName(string $tableName, array $emConfig = []): string
{
    $schema = $emConfig['connection']['schema'] ?? null;
//    $tablePrefix = $emConfig['connection']['table_prefix'] ?? null; // TODO

    if ($schema === null) {
        return $tableName;
    }

    return sprintf('%s.%s', $schema, $tableName);
}

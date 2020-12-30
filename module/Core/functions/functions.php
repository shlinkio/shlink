<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\InputFilter;
use PUGX\Shortid\Factory as ShortIdFactory;

use function sprintf;

const DEFAULT_DELETE_SHORT_URL_THRESHOLD = 15;
const DEFAULT_SHORT_CODES_LENGTH = 5;
const MIN_SHORT_CODES_LENGTH = 4;
const DEFAULT_REDIRECT_STATUS_CODE = StatusCodeInterface::STATUS_FOUND;
const DEFAULT_REDIRECT_CACHE_LIFETIME = 30;
const LOCAL_LOCK_FACTORY = 'Shlinkio\Shlink\LocalLockFactory';
const CUSTOM_SLUGS_REGEXP = '/[^\pL\pN._~]/u'; // Any unicode letter or number, plus ".", "_" and "~" chars

function generateRandomShortCode(int $length): string
{
    static $shortIdFactory;
    if ($shortIdFactory === null) {
        $shortIdFactory = new ShortIdFactory();
    }

    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if ( getenv('SHLINK_ALPHABET') ) { $alphabet = getenv('SHLINK_ALPHABET'); } # override with ENV VAR
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

function getOptionalIntFromInputFilter(InputFilter $inputFilter, string $fieldName): ?int
{
    $value = $inputFilter->getValue($fieldName);
    return $value !== null ? (int) $value : null;
}

function getOptionalBoolFromInputFilter(InputFilter $inputFilter, string $fieldName): ?bool
{
    $value = $inputFilter->getValue($fieldName);
    return $value !== null ? (bool) $value : null;
}

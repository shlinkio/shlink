<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\InputFilter;
use PUGX\Shortid\Factory as ShortIdFactory;
use Shlinkio\Shlink\Common\Util\DateRange;

use function Functional\reduce_left;
use function is_array;
use function lcfirst;
use function print_r;
use function sprintf;
use function str_repeat;
use function str_replace;
use function ucwords;

const DEFAULT_DELETE_SHORT_URL_THRESHOLD = 15;
const DEFAULT_SHORT_CODES_LENGTH = 5;
const MIN_SHORT_CODES_LENGTH = 4;
const DEFAULT_REDIRECT_STATUS_CODE = StatusCodeInterface::STATUS_FOUND;
const DEFAULT_REDIRECT_CACHE_LIFETIME = 30;
const LOCAL_LOCK_FACTORY = 'Shlinkio\Shlink\LocalLockFactory';
const CUSTOM_SLUGS_REGEXP = '/[^\pL\pN._~]/u'; // Any unicode letter or number, plus ".", "_" and "~" chars
const TITLE_TAG_VALUE = '/<title[^>]*>(.*?)<\/title>/i'; // Matches the value inside an html title tag

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

function parseDateRangeFromQuery(array $query, string $startDateName, string $endDateName): DateRange
{
    $startDate = parseDateFromQuery($query, $startDateName);
    $endDate = parseDateFromQuery($query, $endDateName);

    // TODO Use match expression when migrating to PHP8
    if ($startDate === null && $endDate === null) {
        return DateRange::emptyInstance();
    }

    if ($startDate !== null && $endDate !== null) {
        return DateRange::withStartAndEndDate($startDate, $endDate);
    }

    if ($startDate !== null) {
        return DateRange::withStartDate($startDate);
    }

    return DateRange::withEndDate($endDate);
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

function arrayToString(array $array, int $indentSize = 4): string
{
    $indent = str_repeat(' ', $indentSize);
    $index = 0;

    return reduce_left($array, static function ($messages, string $name, $_, string $acc) use (&$index, $indent) {
        $index++;

        return $acc . sprintf(
            "%s%s'%s' => %s",
            $index === 1 ? '' : "\n",
            $indent,
            $name,
            is_array($messages) ? print_r($messages, true) : $messages,
        );
    }, '');
}

function kebabCaseToCamelCase(string $name): string
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $name))));
}

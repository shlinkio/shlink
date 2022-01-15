<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Laminas\InputFilter\InputFilter;
use PUGX\Shortid\Factory as ShortIdFactory;
use Shlinkio\Shlink\Common\Util\DateRange;

use function Functional\reduce_left;
use function is_array;
use function print_r;
use function Shlinkio\Shlink\Common\buildDateRange;
use function sprintf;
use function str_repeat;

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
    return empty($query[$dateName] ?? null) ? null : Chronos::parse($query[$dateName]);
}

function parseDateRangeFromQuery(array $query, string $startDateName, string $endDateName): DateRange
{
    $startDate = parseDateFromQuery($query, $startDateName);
    $endDate = parseDateFromQuery($query, $endDateName);

    return buildDateRange($startDate, $endDate);
}

function parseDateField(string|DateTimeInterface|Chronos|null $date): ?Chronos
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

function isCrawler(string $userAgent): bool
{
    static $detector;
    if ($detector === null) {
        $detector = new CrawlerDetect();
    }

    return $detector->isCrawler($userAgent);
}

function fieldWithUtf8Charset(FieldBuilder $field, array $emConfig, string $collation = 'unicode_ci'): FieldBuilder
{
    return match ($emConfig['connection']['driver'] ?? null) {
        'pdo_mysql' => $field->option('charset', 'utf8mb4')
                             ->option('collation', 'utf8mb4_' . $collation),
        default => $field,
    };
}

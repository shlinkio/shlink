<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use BackedEnum;
use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosInterface;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Laminas\Filter\Word\CamelCaseToSeparator;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Laminas\InputFilter\InputFilter;
use PUGX\Shortid\Factory as ShortIdFactory;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

use function date_default_timezone_get;
use function Functional\map;
use function Functional\reduce_left;
use function is_array;
use function print_r;
use function Shlinkio\Shlink\Common\buildDateRange;
use function sprintf;
use function str_repeat;
use function strtolower;
use function ucfirst;

function generateRandomShortCode(int $length, ShortUrlMode $mode = ShortUrlMode::STRICT): string
{
    static $shortIdFactory;
    if ($shortIdFactory === null) {
        $shortIdFactory = new ShortIdFactory();
    }

    $alphabet = $mode === ShortUrlMode::STRICT
        ? '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        : '0123456789abcdefghijklmnopqrstuvwxyz';
    return $shortIdFactory->generate($length, $alphabet)->serialize();
}

function parseDateFromQuery(array $query, string $dateName): ?Chronos
{
    return normalizeOptionalDate(empty($query[$dateName] ?? null) ? null : Chronos::parse($query[$dateName]));
}

function parseDateRangeFromQuery(array $query, string $startDateName, string $endDateName): DateRange
{
    $startDate = parseDateFromQuery($query, $startDateName);
    $endDate = parseDateFromQuery($query, $endDateName);

    return buildDateRange($startDate, $endDate);
}

/**
 * @return ($date is null ? null : Chronos)
 */
function normalizeOptionalDate(string|DateTimeInterface|ChronosInterface|null $date): ?Chronos
{
    $parsedDate = match (true) {
        $date === null || $date instanceof Chronos => $date,
        $date instanceof DateTimeInterface => Chronos::instance($date),
        default => Chronos::parse($date),
    };

    return $parsedDate?->setTimezone(date_default_timezone_get());
}

function normalizeDate(string|DateTimeInterface|ChronosInterface $date): Chronos
{
    return normalizeOptionalDate($date);
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

function getNonEmptyOptionalValueFromInputFilter(InputFilter $inputFilter, string $fieldName): mixed
{
    $value = $inputFilter->getValue($fieldName);
    return empty($value) ? null : $value;
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

function determineTableName(string $tableName, array $emConfig = []): string
{
    $schema = $emConfig['connection']['schema'] ?? null;
//    $tablePrefix = $emConfig['connection']['table_prefix'] ?? null; // TODO

    if ($schema === null) {
        return $tableName;
    }

    return sprintf('%s.%s', $schema, $tableName);
}

function fieldWithUtf8Charset(FieldBuilder $field, array $emConfig, string $collation = 'unicode_ci'): FieldBuilder
{
    return match ($emConfig['connection']['driver'] ?? null) {
        'pdo_mysql' => $field->option('charset', 'utf8mb4')
                             ->option('collation', 'utf8mb4_' . $collation),
        default => $field,
    };
}

function camelCaseToHumanFriendly(string $value): string
{
    static $filter;
    if ($filter === null) {
        $filter = new CamelCaseToSeparator(' ');
    }

    return ucfirst($filter->filter($value));
}

function camelCaseToSnakeCase(string $value): string
{
    static $filter;
    if ($filter === null) {
        $filter = new CamelCaseToUnderscore();
    }

    return strtolower($filter->filter($value));
}

function toProblemDetailsType(string $errorCode): string
{
    return sprintf('https://shlink.io/api/error/%s', $errorCode);
}

/**
 * @param class-string<BackedEnum> $enum
 * @return string[]
 */
function enumValues(string $enum): array
{
    static $cache;
    if ($cache === null) {
        $cache = [];
    }

    return $cache[$enum] ?? (
        $cache[$enum] = map($enum::cases(), static fn (BackedEnum $type) => (string) $type->value)
    );
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use BackedEnum;
use Cake\Chronos\Chronos;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use GuzzleHttp\Psr7\Query;
use Hidehalo\Nanoid\Client as NanoidClient;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Laminas\Filter\Word\CamelCaseToSeparator;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Laminas\InputFilter\InputFilter;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Middleware\IpAddressMiddlewareFactory;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

use function array_keys;
use function array_map;
use function array_pad;
use function array_reduce;
use function date_default_timezone_get;
use function explode;
use function implode;
use function is_array;
use function print_r;
use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\ArrayUtils\map;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strtolower;
use function trim;
use function ucfirst;

function generateRandomShortCode(int $length, ShortUrlMode $mode = ShortUrlMode::STRICT): string
{
    static $nanoIdClient;
    if ($nanoIdClient === null) {
        $nanoIdClient = new NanoidClient();
    }

    $alphabet = $mode === ShortUrlMode::STRICT
        ? '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        : '0123456789abcdefghijklmnopqrstuvwxyz';
    return $nanoIdClient->formattedId($alphabet, $length);
}

function parseDateFromQuery(array $query, string $dateName): Chronos|null
{
    return normalizeOptionalDate(empty($query[$dateName] ?? null) ? null : Chronos::parse($query[$dateName]));
}

function parseDateRangeFromQuery(array $query, string $startDateName, string $endDateName): DateRange
{
    $startDate = parseDateFromQuery($query, $startDateName);
    $endDate = parseDateFromQuery($query, $endDateName);

    return buildDateRange($startDate, $endDate);
}

function dateRangeToHumanFriendly(DateRange|null $dateRange): string
{
    $startDate = $dateRange?->startDate;
    $endDate = $dateRange?->endDate;

    return match (true) {
        $startDate !== null && $endDate !== null => sprintf(
            'Between %s and %s',
            $startDate->toDateTimeString(),
            $endDate->toDateTimeString(),
        ),
        $startDate !== null => sprintf('Since %s', $startDate->toDateTimeString()),
        $endDate !== null => sprintf('Until %s', $endDate->toDateTimeString()),
        default => 'All time',
    };
}

/**
 * @return ($date is null ? null : Chronos)
 */
function normalizeOptionalDate(string|DateTimeInterface|Chronos|null $date): Chronos|null
{
    $parsedDate = match (true) {
        $date === null || $date instanceof Chronos => $date,
        $date instanceof DateTimeInterface => Chronos::instance($date),
        default => Chronos::parse($date),
    };

    return $parsedDate?->setTimezone(date_default_timezone_get());
}

function normalizeDate(string|DateTimeInterface|Chronos $date): Chronos
{
    return normalizeOptionalDate($date);
}

function normalizeLocale(string $locale): string
{
    return trim(strtolower(str_replace('_', '-', $locale)));
}

/**
 * Parse an accept-language-like pattern into a list of locales, optionally filtering out those which do not match a
 * minimum quality
 *
 * @param non-empty-string $acceptLanguage
 * @return iterable<string>;
 */
function acceptLanguageToLocales(string $acceptLanguage, float $minQuality = 0): iterable
{
    /** @var array{string, float|null}[] $acceptLanguagesList */
    $acceptLanguagesList = map(explode(',', $acceptLanguage), static function (string $lang): array {
        // Split locale/language and quality (en-US;q=0.7) -> [en-US, q=0.7]
        [$lang, $qualityString] = array_pad(explode(';', $lang), length: 2, value: '');
        $normalizedLang = normalizeLocale($lang);
        $quality = Query::parse(trim($qualityString))['q'] ?? 1;

        return [$normalizedLang, (float) $quality];
    });

    foreach ($acceptLanguagesList as [$lang, $quality]) {
        if ($lang !== '*' && $quality >= $minQuality) {
            yield $lang;
        }
    }
}

/**
 * Splits a locale into its corresponding language and country codes.
 * The country code will be null if not present
 *   'es-AR' -> ['es', 'AR']
 *   'fr-FR' -> ['fr', 'FR']
 *   'en' -> ['en', null]
 *
 * @return array{string, string|null}
 */
function splitLocale(string $locale): array
{
    [$lang, $countryCode] = array_pad(explode('-', $locale), length: 2, value: null);
    return [$lang, $countryCode];
}

/**
 * @param InputFilter<mixed> $inputFilter
 */
function getOptionalIntFromInputFilter(InputFilter $inputFilter, string $fieldName): int|null
{
    $value = $inputFilter->getValue($fieldName);
    return $value !== null ? (int) $value : null;
}

/**
 * @param InputFilter<mixed> $inputFilter
 */
function getOptionalBoolFromInputFilter(InputFilter $inputFilter, string $fieldName): bool|null
{
    $value = $inputFilter->getValue($fieldName);
    return $value !== null ? (bool) $value : null;
}

/**
 * @param InputFilter<mixed> $inputFilter
 */
function getNonEmptyOptionalValueFromInputFilter(InputFilter $inputFilter, string $fieldName): mixed
{
    $value = $inputFilter->getValue($fieldName);
    return empty($value) ? null : $value;
}

function arrayToString(array $array, int $indentSize = 4): string
{
    $indent = str_repeat(' ', $indentSize);
    $names = array_keys($array);
    $index = 0;

    return array_reduce($names, static function (string $acc, string $name) use (&$index, $indent, $array) {
        $index++;
        $messages = $array[$name];

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
        $cache[$enum] = array_map(static fn (BackedEnum $type) => (string) $type->value, $enum::cases())
    );
}

/**
 * @param class-string<BackedEnum> $enum
 */
function enumToString(string $enum): string
{
    return sprintf('["%s"]', implode('", "', enumValues($enum)));
}

/**
 * Split provided string by comma and return a list of the results.
 * An empty array is returned if provided value is empty
 */
function splitByComma(string|null $value): array
{
    if ($value === null || trim($value) === '') {
        return [];
    }

    return array_map(trim(...), explode(',', $value));
}

function ipAddressFromRequest(ServerRequestInterface $request): string|null
{
    return $request->getAttribute(IpAddressMiddlewareFactory::REQUEST_ATTR);
}

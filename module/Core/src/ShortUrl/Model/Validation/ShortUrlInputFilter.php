<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use DateTimeInterface;
use Laminas\Filter;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function is_string;
use function str_replace;
use function substr;
use function trim;

use const Shlinkio\Shlink\MIN_SHORT_CODES_LENGTH;

class ShortUrlInputFilter extends InputFilter
{
    use Validation\InputFactoryTrait;

    public const VALID_SINCE = 'validSince';
    public const VALID_UNTIL = 'validUntil';
    public const CUSTOM_SLUG = 'customSlug';
    public const MAX_VISITS = 'maxVisits';
    public const FIND_IF_EXISTS = 'findIfExists';
    public const DOMAIN = 'domain';
    public const SHORT_CODE_LENGTH = 'shortCodeLength';
    public const LONG_URL = 'longUrl';
    public const DEVICE_LONG_URLS = 'deviceLongUrls';
    public const VALIDATE_URL = 'validateUrl';
    public const API_KEY = 'apiKey';
    public const TAGS = 'tags';
    public const TITLE = 'title';
    public const CRAWLABLE = 'crawlable';
    public const FORWARD_QUERY = 'forwardQuery';

    private function __construct(array $data, bool $requireLongUrl)
    {
        // FIXME The multi-segment slug option should be injected
        $this->initialize($requireLongUrl, $data[EnvVars::MULTI_SEGMENT_SLUGS_ENABLED->value] ?? false);
        $this->setData($data);
    }

    public static function withRequiredLongUrl(array $data): self
    {
        return new self($data, true);
    }

    public static function withNonRequiredLongUrl(array $data): self
    {
        return new self($data, false);
    }

    private function initialize(bool $requireLongUrl, bool $multiSegmentEnabled): void
    {
        $longUrlNotEmptyCommonOptions = [
            Validator\NotEmpty::OBJECT,
            Validator\NotEmpty::SPACE,
            Validator\NotEmpty::EMPTY_ARRAY,
            Validator\NotEmpty::BOOLEAN,
            Validator\NotEmpty::STRING,
        ];

        $longUrlInput = $this->createInput(self::LONG_URL, $requireLongUrl);
        $longUrlInput->getValidatorChain()->attach(new Validator\NotEmpty([
            ...$longUrlNotEmptyCommonOptions,
            Validator\NotEmpty::NULL,
        ]));
        $this->add($longUrlInput);

        $deviceLongUrlsInput = $this->createInput(self::DEVICE_LONG_URLS, false);
        $deviceLongUrlsInput->getValidatorChain()->attach(
            new DeviceLongUrlsValidator(new Validator\NotEmpty([
                ...$longUrlNotEmptyCommonOptions,
                ...($requireLongUrl ? [Validator\NotEmpty::NULL] : []),
            ])),
        );
        $this->add($deviceLongUrlsInput);

        $validSince = $this->createInput(self::VALID_SINCE, false);
        $validSince->getValidatorChain()->attach(new Validator\Date(['format' => DateTimeInterface::ATOM]));
        $this->add($validSince);

        $validUntil = $this->createInput(self::VALID_UNTIL, false);
        $validUntil->getValidatorChain()->attach(new Validator\Date(['format' => DateTimeInterface::ATOM]));
        $this->add($validUntil);

        // The only way to enforce the NotEmpty validator to be evaluated when the key is present with an empty value
        // is by using the deprecated setContinueIfEmpty
        $customSlug = $this->createInput(self::CUSTOM_SLUG, false)->setContinueIfEmpty(true);
        $customSlug->getFilterChain()->attach(new Filter\Callback(match ($multiSegmentEnabled) {
            true => static fn (mixed $v) => is_string($v) ? trim(str_replace(' ', '-', $v), '/') : $v,
            false => static fn (mixed $v) => is_string($v) ? str_replace([' ', '/'], '-', $v) : $v,
        }));
        $customSlug->getValidatorChain()->attach(new Validator\NotEmpty([
            Validator\NotEmpty::STRING,
            Validator\NotEmpty::SPACE,
        ]));
        $this->add($customSlug);

        $this->add($this->createNumericInput(self::MAX_VISITS, false));
        $this->add($this->createNumericInput(self::SHORT_CODE_LENGTH, false, MIN_SHORT_CODES_LENGTH));

        $this->add($this->createBooleanInput(self::FIND_IF_EXISTS, false));

        // These cannot be defined as a boolean inputs, because they can actually have 3 values: true, false and null.
        // Defining them as boolean will make null fall back to false, which is not the desired behavior.
        $this->add($this->createInput(self::VALIDATE_URL, false));
        $this->add($this->createInput(self::FORWARD_QUERY, false));

        $domain = $this->createInput(self::DOMAIN, false);
        $domain->getValidatorChain()->attach(new Validation\HostAndPortValidator());
        $this->add($domain);

        $apiKeyInput = $this->createInput(self::API_KEY, false);
        $apiKeyInput->getValidatorChain()->attach(new Validator\IsInstanceOf(['className' => ApiKey::class]));
        $this->add($apiKeyInput);

        $this->add($this->createTagsInput(self::TAGS, false));

        $title = $this->createInput(self::TITLE, false);
        $title->getFilterChain()->attach(new Filter\Callback(
            static fn (?string $value) => $value === null ? $value : substr($value, 0, 512),
        ));
        $this->add($title);

        $this->add($this->createBooleanInput(self::CRAWLABLE, false));
    }
}

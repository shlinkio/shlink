<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use DateTime;
use Laminas\Filter;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function is_string;
use function str_replace;
use function substr;

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
    public const VALIDATE_URL = 'validateUrl';
    public const API_KEY = 'apiKey';
    public const TAGS = 'tags';
    public const TITLE = 'title';
    public const CRAWLABLE = 'crawlable';
    public const FORWARD_QUERY = 'forwardQuery';

    private function __construct(array $data, bool $requireLongUrl)
    {
        $this->initialize($requireLongUrl);
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

    private function initialize(bool $requireLongUrl): void
    {
        $longUrlInput = $this->createInput(self::LONG_URL, $requireLongUrl);
        $longUrlInput->getValidatorChain()->attach(new Validator\NotEmpty([
            Validator\NotEmpty::OBJECT,
            Validator\NotEmpty::SPACE,
            Validator\NotEmpty::NULL,
            Validator\NotEmpty::EMPTY_ARRAY,
            Validator\NotEmpty::BOOLEAN,
        ]));
        $this->add($longUrlInput);

        $validSince = $this->createInput(self::VALID_SINCE, false);
        $validSince->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($validSince);

        $validUntil = $this->createInput(self::VALID_UNTIL, false);
        $validUntil->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($validUntil);

        // FIXME The only way to enforce the NotEmpty validator to be evaluated when the value is provided but it's
        //       empty, is by using the deprecated setContinueIfEmpty
        $customSlug = $this->createInput(self::CUSTOM_SLUG, false)->setContinueIfEmpty(true);
        $customSlug->getFilterChain()->attach(new Filter\Callback(
            static fn (mixed $value) => is_string($value) ? str_replace([' ', '/'], '-', $value) : $value,
        ));
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

        $apiKeyInput = new Input(self::API_KEY);
        $apiKeyInput
            ->setRequired(false)
            ->getValidatorChain()->attach(new Validator\IsInstanceOf(['className' => ApiKey::class]));
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

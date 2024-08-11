<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use DateTimeInterface;
use Laminas\Filter;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation\HostAndPortValidator;
use Shlinkio\Shlink\Common\Validation\InputFactory;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function is_string;
use function preg_match;
use function substr;

use const Shlinkio\Shlink\LOOSE_URI_MATCHER;
use const Shlinkio\Shlink\MIN_SHORT_CODES_LENGTH;

/** @extends InputFilter<mixed> */
class ShortUrlInputFilter extends InputFilter
{
    // Fields for creation only
    public const SHORT_CODE_LENGTH = 'shortCodeLength';
    public const CUSTOM_SLUG = 'customSlug';
    public const PATH_PREFIX = 'pathPrefix';
    public const FIND_IF_EXISTS = 'findIfExists';
    public const DOMAIN = 'domain';

    // Fields for creation and edition
    public const LONG_URL = 'longUrl';
    public const VALID_SINCE = 'validSince';
    public const VALID_UNTIL = 'validUntil';
    public const MAX_VISITS = 'maxVisits';
    public const TITLE = 'title';
    public const TAGS = 'tags';
    public const CRAWLABLE = 'crawlable';
    public const FORWARD_QUERY = 'forwardQuery';
    public const API_KEY = 'apiKey';

    public static function forCreation(array $data, UrlShortenerOptions $options): self
    {
        $instance = new self();
        $instance->initializeForCreation($options);
        $instance->setData($data);

        return $instance;
    }

    public static function forEdition(array $data): self
    {
        $instance = new self();
        $instance->initializeForEdition();
        $instance->setData($data);

        return $instance;
    }

    private function initializeForCreation(UrlShortenerOptions $options): void
    {
        // The only way to enforce the NotEmpty validator to be evaluated when the key is present with an empty value
        // is with setContinueIfEmpty(true)
        $customSlug = InputFactory::basic(self::CUSTOM_SLUG)->setContinueIfEmpty(true);
        $customSlug->getFilterChain()->attach(new CustomSlugFilter($options));
        $customSlug->getValidatorChain()
            ->attach(new Validator\NotEmpty([
                Validator\NotEmpty::STRING,
                Validator\NotEmpty::SPACE,
            ]))
            ->attach(CustomSlugValidator::forUrlShortenerOptions($options));
        $this->add($customSlug);

        // The path prefix is subject to the same filtering and validation logic as the custom slug, which takes into
        // consideration if multi-segment slugs are enabled or not.
        // The only difference is that empty values are allowed here.
        $pathPrefix = InputFactory::basic(self::PATH_PREFIX);
        $pathPrefix->getFilterChain()->attach(new CustomSlugFilter($options));
        $pathPrefix->getValidatorChain()->attach(CustomSlugValidator::forUrlShortenerOptions($options));
        $this->add($pathPrefix);

        $this->add(InputFactory::numeric(self::SHORT_CODE_LENGTH, min: MIN_SHORT_CODES_LENGTH));
        $this->add(InputFactory::boolean(self::FIND_IF_EXISTS));

        $domain = InputFactory::basic(self::DOMAIN);
        $domain->getValidatorChain()->attach(new HostAndPortValidator());
        $this->add($domain);

        $this->initializeForEdition(requireLongUrl: true);
    }

    private function initializeForEdition(bool $requireLongUrl = false): void
    {
        $longUrlInput = InputFactory::basic(self::LONG_URL, required: $requireLongUrl);
        $longUrlInput->getValidatorChain()->merge(self::longUrlValidators(allowNull: ! $requireLongUrl));
        $this->add($longUrlInput);

        $validSince = InputFactory::basic(self::VALID_SINCE);
        $validSince->getValidatorChain()->attach(new Validator\Date(['format' => DateTimeInterface::ATOM]));
        $this->add($validSince);

        $validUntil = InputFactory::basic(self::VALID_UNTIL);
        $validUntil->getValidatorChain()->attach(new Validator\Date(['format' => DateTimeInterface::ATOM]));
        $this->add($validUntil);

        $this->add(InputFactory::numeric(self::MAX_VISITS));

        $title = InputFactory::basic(self::TITLE);
        $title->getFilterChain()->attach(new Filter\Callback(
            static fn (?string $value) => $value === null ? $value : substr($value, 0, 512),
        ));
        $this->add($title);

        $this->add(InputFactory::tags(self::TAGS));
        $this->add(InputFactory::boolean(self::CRAWLABLE));

        // This cannot be defined as a boolean inputs, because it can actually have 3 values: true, false and null.
        // Defining them as boolean will make null fall back to false, which is not the desired behavior.
        $this->add(InputFactory::basic(self::FORWARD_QUERY));

        $apiKeyInput = InputFactory::basic(self::API_KEY);
        $apiKeyInput->getValidatorChain()->attach(new Validator\IsInstanceOf(['className' => ApiKey::class]));
        $this->add($apiKeyInput);
    }

    /**
     * @todo Extract to its own validator class
     */
    public static function longUrlValidators(bool $allowNull = false): Validator\ValidatorChain
    {
        $emptyModifiers = [
            Validator\NotEmpty::OBJECT,
            Validator\NotEmpty::SPACE,
            Validator\NotEmpty::EMPTY_ARRAY,
            Validator\NotEmpty::BOOLEAN,
            Validator\NotEmpty::STRING,
        ];
        if (! $allowNull) {
            $emptyModifiers[] = Validator\NotEmpty::NULL;
        }

        return (new Validator\ValidatorChain())
            ->attach(new Validator\NotEmpty($emptyModifiers))
            ->attach(new Validator\Callback(
                // Non-strings is always allowed. Other validators will take care of those
                static fn (mixed $value) => ! is_string($value) || preg_match(LOOSE_URI_MATCHER, $value) === 1,
            ));
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use Cocur\Slugify\Slugify;
use DateTime;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation;
use Shlinkio\Shlink\Core\Util\CocurSymfonySluggerBridge;

use const Shlinkio\Shlink\Core\CUSTOM_SLUGS_REGEXP;
use const Shlinkio\Shlink\Core\MIN_SHORT_CODES_LENGTH;

class ShortUrlMetaInputFilter extends InputFilter
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

    public function __construct(array $data)
    {
        $this->initialize();
        $this->setData($data);
    }

    private function initialize(): void
    {
        $this->add($this->createInput(self::LONG_URL, false));

        $validSince = $this->createInput(self::VALID_SINCE, false);
        $validSince->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($validSince);

        $validUntil = $this->createInput(self::VALID_UNTIL, false);
        $validUntil->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($validUntil);

        // FIXME The only way to enforce the NotEmpty validator to be evaluated when the value is provided but it's
        //       empty, is by using the deprecated setContinueIfEmpty
        $customSlug = $this->createInput(self::CUSTOM_SLUG, false)->setContinueIfEmpty(true);
        $customSlug->getFilterChain()->attach(new Validation\SluggerFilter(new CocurSymfonySluggerBridge(new Slugify([
            'regexp' => CUSTOM_SLUGS_REGEXP,
            'lowercase' => false, // We want to keep it case sensitive
        ]))));
        $customSlug->getValidatorChain()->attach(new Validator\NotEmpty([
            Validator\NotEmpty::STRING,
            Validator\NotEmpty::SPACE,
        ]));
        $this->add($customSlug);

        $this->add($this->createPositiveNumberInput(self::MAX_VISITS));
        $this->add($this->createPositiveNumberInput(self::SHORT_CODE_LENGTH, MIN_SHORT_CODES_LENGTH));

        $this->add($this->createBooleanInput(self::FIND_IF_EXISTS, false));

        $this->add($this->createInput(self::VALIDATE_URL, false));

        $domain = $this->createInput(self::DOMAIN, false);
        $domain->getValidatorChain()->attach(new Validation\HostAndPortValidator());
        $this->add($domain);
    }

    private function createPositiveNumberInput(string $name, int $min = 1): Input
    {
        $input = $this->createInput($name, false);
        $input->getValidatorChain()->attach(new Validator\Digits())
                                   ->attach(new Validator\GreaterThan(['min' => $min, 'inclusive' => true]));

        return $input;
    }
}

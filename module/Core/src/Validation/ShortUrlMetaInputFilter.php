<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use DateTime;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation;

class ShortUrlMetaInputFilter extends InputFilter
{
    use Validation\InputFactoryTrait;

    public const VALID_SINCE = 'validSince';
    public const VALID_UNTIL = 'validUntil';
    public const CUSTOM_SLUG = 'customSlug';
    public const MAX_VISITS = 'maxVisits';
    public const FIND_IF_EXISTS = 'findIfExists';
    public const DOMAIN = 'domain';

    public function __construct(array $data)
    {
        $this->initialize();
        $this->setData($data);
    }

    private function initialize(): void
    {
        $validSince = $this->createInput(self::VALID_SINCE, false);
        $validSince->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($validSince);

        $validUntil = $this->createInput(self::VALID_UNTIL, false);
        $validUntil->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($validUntil);

        $customSlug = $this->createInput(self::CUSTOM_SLUG, false);
        $customSlug->getFilterChain()->attach(new Validation\SluggerFilter());
        $this->add($customSlug);

        $maxVisits = $this->createInput(self::MAX_VISITS, false);
        $maxVisits->getValidatorChain()->attach(new Validator\Digits())
                                       ->attach(new Validator\GreaterThan(['min' => 1, 'inclusive' => true]));
        $this->add($maxVisits);

        $this->add($this->createBooleanInput(self::FIND_IF_EXISTS, false));

        $domain = $this->createInput(self::DOMAIN, false);
        $domain->getValidatorChain()->attach(new Validation\HostAndPortValidator());
        $this->add($domain);
    }
}

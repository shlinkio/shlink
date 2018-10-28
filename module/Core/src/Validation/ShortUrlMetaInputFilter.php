<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use DateTime;
use Zend\I18n\Validator\IsInt;
use Zend\InputFilter\InputFilter;
use Zend\Validator\Date;
use Zend\Validator\GreaterThan;

class ShortUrlMetaInputFilter extends InputFilter
{
    use InputFactoryTrait;

    public const VALID_SINCE = 'validSince';
    public const VALID_UNTIL = 'validUntil';
    public const CUSTOM_SLUG = 'customSlug';
    public const MAX_VISITS = 'maxVisits';

    public function __construct(?array $data = null)
    {
        $this->initialize();
        if ($data !== null) {
            $this->setData($data);
        }
    }

    private function initialize(): void
    {
        $validSince = $this->createInput(self::VALID_SINCE, false);
        $validSince->getValidatorChain()->attach(new Date(['format' => DateTime::ATOM]));
        $this->add($validSince);

        $validUntil = $this->createInput(self::VALID_UNTIL, false);
        $validUntil->getValidatorChain()->attach(new Date(['format' => DateTime::ATOM]));
        $this->add($validUntil);

        $this->add($this->createInput(self::CUSTOM_SLUG, false));

        $maxVisits = $this->createInput(self::MAX_VISITS, false);
        $maxVisits->getValidatorChain()->attach(new IsInt())
                                       ->attach(new GreaterThan(['min' => 1, 'inclusive' => true]));
        $this->add($maxVisits);
    }
}

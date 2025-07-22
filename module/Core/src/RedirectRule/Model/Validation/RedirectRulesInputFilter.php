<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\RedirectRule\Model\Validation;

use Laminas\InputFilter\CollectionInputFilter;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\InArray;
use Shlinkio\Shlink\Common\Validation\InputFactory;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;

use function Shlinkio\Shlink\Core\enumValues;

/** @extends InputFilter<mixed> */
class RedirectRulesInputFilter extends InputFilter
{
    public const string REDIRECT_RULES = 'redirectRules';

    public const string RULE_LONG_URL = 'longUrl';
    public const string RULE_CONDITIONS = 'conditions';

    public const string CONDITION_TYPE = 'type';
    public const string CONDITION_MATCH_VALUE = 'matchValue';
    public const string CONDITION_MATCH_KEY = 'matchKey';

    private function __construct()
    {
    }

    public static function initialize(array $rawData): self
    {
        $redirectRulesInputFilter = new CollectionInputFilter();
        $redirectRulesInputFilter->setInputFilter(self::createRedirectRuleInputFilter());

        $instance = new self();
        $instance->add($redirectRulesInputFilter, self::REDIRECT_RULES);

        $instance->setData($rawData);
        return $instance;
    }

    /**
     * @return InputFilter<mixed>
     */
    private static function createRedirectRuleInputFilter(): InputFilter
    {
        $redirectRuleInputFilter = new InputFilter();

        $longUrl = InputFactory::basic(self::RULE_LONG_URL, required: true);
        $longUrl->getValidatorChain()->merge(ShortUrlInputFilter::longUrlValidators());
        $redirectRuleInputFilter->add($longUrl);

        $conditionsInputFilter = new CollectionInputFilter();
        $conditionsInputFilter->setInputFilter(self::createRedirectConditionInputFilter())
                              ->setIsRequired(true);
        $redirectRuleInputFilter->add($conditionsInputFilter, self::RULE_CONDITIONS);

        return $redirectRuleInputFilter;
    }

    /**
     * @return InputFilter<mixed>
     */
    private static function createRedirectConditionInputFilter(): InputFilter
    {
        $redirectConditionInputFilter = new InputFilter();

        $type = InputFactory::basic(self::CONDITION_TYPE, required: true);
        $type->getValidatorChain()->attach(new InArray([
            'haystack' => enumValues(RedirectConditionType::class),
            'strict' => InArray::COMPARE_STRICT,
        ]));
        $redirectConditionInputFilter->add($type);

        $value = InputFactory::basic(self::CONDITION_MATCH_VALUE, required: true)->setAllowEmpty(true);
        $value->getValidatorChain()->attach(new Callback(
            function (string $value, array $context): bool {
                $conditionType = RedirectConditionType::tryFrom($context[self::CONDITION_TYPE]);
                return $conditionType === null || $conditionType->isValid($value);
            },
        ));
        $redirectConditionInputFilter->add($value);

        $redirectConditionInputFilter->add(
            InputFactory::basic(self::CONDITION_MATCH_KEY, required: true)->setAllowEmpty(true),
        );

        return $redirectConditionInputFilter;
    }
}

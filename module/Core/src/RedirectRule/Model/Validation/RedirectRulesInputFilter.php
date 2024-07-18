<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\RedirectRule\Model\Validation;

use Laminas\InputFilter\CollectionInputFilter;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\InArray;
use Shlinkio\Shlink\Common\Validation\InputFactory;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\Util\IpAddressUtils;

use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\enumValues;

class RedirectRulesInputFilter extends InputFilter
{
    public const REDIRECT_RULES = 'redirectRules';

    public const RULE_LONG_URL = 'longUrl';
    public const RULE_CONDITIONS = 'conditions';

    public const CONDITION_TYPE = 'type';
    public const CONDITION_MATCH_VALUE = 'matchValue';
    public const CONDITION_MATCH_KEY = 'matchKey';

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

    private static function createRedirectConditionInputFilter(): InputFilter
    {
        $redirectConditionInputFilter = new InputFilter();

        $type = InputFactory::basic(self::CONDITION_TYPE, required: true);
        $type->getValidatorChain()->attach(new InArray([
            'haystack' => enumValues(RedirectConditionType::class),
            'strict' => InArray::COMPARE_STRICT,
        ]));
        $redirectConditionInputFilter->add($type);

        $value = InputFactory::basic(self::CONDITION_MATCH_VALUE, required: true);
        $value->getValidatorChain()->attach(new Callback(
            fn (string $value, array $context) => match ($context[self::CONDITION_TYPE]) {
                RedirectConditionType::DEVICE->value => contains($value, enumValues(DeviceType::class)),
                RedirectConditionType::IP_ADDRESS->value => IpAddressUtils::isStaticIpCidrOrWildcard($value),
                // RedirectConditionType::LANGUAGE->value => TODO,
                default => true,
            },
        ));
        $redirectConditionInputFilter->add($value);

        $redirectConditionInputFilter->add(
            InputFactory::basic(self::CONDITION_MATCH_KEY, required: true)->setAllowEmpty(true),
        );

        return $redirectConditionInputFilter;
    }
}

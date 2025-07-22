<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\RedirectRule\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;

class RedirectConditionTypeTest extends TestCase
{
    #[Test]
    #[TestWith([RedirectConditionType::QUERY_PARAM, '', false])]
    #[TestWith([RedirectConditionType::QUERY_PARAM, 'foo', true])]
    #[TestWith([RedirectConditionType::ANY_VALUE_QUERY_PARAM, '', true])]
    #[TestWith([RedirectConditionType::ANY_VALUE_QUERY_PARAM, 'foo', true])]
    #[TestWith([RedirectConditionType::VALUELESS_QUERY_PARAM, '', true])]
    #[TestWith([RedirectConditionType::VALUELESS_QUERY_PARAM, 'foo', true])]
    public function isValidFailsForEmptyQueryParams(
        RedirectConditionType $conditionType,
        string $value,
        bool $expectedIsValid,
    ): void {
        self::assertEquals($expectedIsValid, $conditionType->isValid($value));
    }
}

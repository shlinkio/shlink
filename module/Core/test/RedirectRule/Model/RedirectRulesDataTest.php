<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\RedirectRule\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectRulesData;

class RedirectRulesDataTest extends TestCase
{
    #[Test]
    #[TestWith([['redirectRules' => ['foo']]])]
    #[TestWith([['redirectRules' => [
        ['priority' => 'foo'],
    ]]])]
    #[TestWith([['redirectRules' => [
        [
            'priority' => 4,
            'longUrl' => 34,
        ],
    ]]])]
    #[TestWith([['redirectRules' => [
        [
            'priority' => 4,
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'invalid',
                ],
            ],
        ],
    ]]])]
    #[TestWith([['redirectRules' => [
        [
            'priority' => 4,
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'device',
                    'matchValue' => 'invalid-device',
                    'matchKey' => null,
                ],
            ],
        ],
    ]]])]
    #[TestWith([['redirectRules' => [
        [
            'priority' => 4,
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'language',
                ],
            ],
        ],
    ]]])]
    public function throwsWhenProvidedDataIsInvalid(array $invalidData): void
    {
        $this->expectException(ValidationException::class);
        RedirectRulesData::fromRawData($invalidData);
    }
}

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
        [
            'longUrl' => 34,
        ],
    ]]])]
    #[TestWith([['redirectRules' => [
        [
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
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'language',
                ],
            ],
        ],
    ]]])]
    #[TestWith([['redirectRules' => [
        [
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'ip-address',
                    'matchKey' => null,
                    'matchValue' => 'not an IP address',
                ],
            ],
        ],
    ]]])]
    public function throwsWhenProvidedDataIsInvalid(array $invalidData): void
    {
        $this->expectException(ValidationException::class);
        RedirectRulesData::fromRawData($invalidData);
    }

    #[Test]
    #[TestWith([['redirectRules' => [
        [
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'ip-address',
                    'matchKey' => null,
                    'matchValue' => '1.2.3.4',
                ],
            ],
        ],
    ]]], 'static IP')]
    #[TestWith([['redirectRules' => [
        [
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'ip-address',
                    'matchKey' => null,
                    'matchValue' => '1.2.3.0/24',
                ],
            ],
        ],
    ]]], 'CIDR block')]
    #[TestWith([['redirectRules' => [
        [
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'ip-address',
                    'matchKey' => null,
                    'matchValue' => '1.2.3.*',
                ],
            ],
        ],
    ]]], 'IP wildcard pattern')]
    #[TestWith([['redirectRules' => [
        [
            'longUrl' => 'https://example.com',
            'conditions' => [
                [
                    'type' => 'ip-address',
                    'matchKey' => null,
                    'matchValue' => '1.2.*.4',
                ],
            ],
        ],
    ]]], 'in-between IP wildcard pattern')]
    public function allowsValidDataToBeSet(array $validData): void
    {
        $result = RedirectRulesData::fromRawData($validData);
        self::assertEquals($result->rules, $validData['redirectRules']);
    }
}

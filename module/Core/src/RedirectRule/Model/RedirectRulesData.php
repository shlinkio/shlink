<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\RedirectRule\Model;

readonly class RedirectRulesData
{
    /**
     * @param RedirectRuleData[] $redirectRules
     */
    public function __construct(public array $redirectRules)
    {
    }
}

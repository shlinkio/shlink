<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\RedirectRule\Model;

use Shlinkio\Shlink\Common\ObjectMapper\LooseUriConverter;

readonly class RedirectRuleData
{
    /**
     * @param RedirectConditionData[] $conditions
     */
    public function __construct(
        #[LooseUriConverter]
        public string $longUrl,
        public array $conditions,
    ) {
    }
}

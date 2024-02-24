<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;

class RedirectCondition extends AbstractEntity
{
    public function __construct(
        public readonly string $name,
        public readonly RedirectConditionType $type,
        public readonly string $matchValue,
        public readonly ?string $matchKey = null,
    ) {
    }
}

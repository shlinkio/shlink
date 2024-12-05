<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Transformer;

use Shlinkio\Shlink\Core\Visit\Entity\Visit;

interface VisitDataTransformerInterface
{
    public function transform(Visit $visit): array;
}

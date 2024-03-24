<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

class OrphanVisitDataTransformer implements DataTransformerInterface
{
    /**
     * @param Visit $visit
     */
    public function transform($visit): array // phpcs:ignore
    {
        return $visit->jsonSerialize();
    }
}

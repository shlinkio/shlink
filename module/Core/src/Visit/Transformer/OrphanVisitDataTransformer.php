<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;

class OrphanVisitDataTransformer implements DataTransformerInterface
{
    /**
     * @param Visit $visit
     * @return array
     */
    public function transform($visit): array // phpcs:ignore
    {
        $serializedVisit = $visit->jsonSerialize();
        $serializedVisit['visitedUrl'] = $visit->visitedUrl();
        $serializedVisit['type'] = $visit->type();

        return $serializedVisit;
    }
}

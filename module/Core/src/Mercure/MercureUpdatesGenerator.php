<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Mercure;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Symfony\Component\Mercure\Update;

use function Shlinkio\Shlink\Common\json_encode;

final class MercureUpdatesGenerator implements MercureUpdatesGeneratorInterface
{
    public function __construct(
        private DataTransformerInterface $shortUrlTransformer,
        private DataTransformerInterface $orphanVisitTransformer,
    ) {
    }

    public function newVisitUpdate(Visit $visit): Update
    {
        return new Update(Topic::NEW_VISIT->value, json_encode([
            'shortUrl' => $this->shortUrlTransformer->transform($visit->getShortUrl()),
            'visit' => $visit,
        ]));
    }

    public function newOrphanVisitUpdate(Visit $visit): Update
    {
        return new Update(Topic::NEW_ORPHAN_VISIT->value, json_encode([
            'visit' => $this->orphanVisitTransformer->transform($visit),
        ]));
    }

    public function newShortUrlVisitUpdate(Visit $visit): Update
    {
        $shortUrl = $visit->getShortUrl();
        $topic = Topic::newShortUrlVisit($shortUrl?->getShortCode());

        return new Update($topic, json_encode([
            'shortUrl' => $this->shortUrlTransformer->transform($shortUrl),
            'visit' => $visit,
        ]));
    }
}

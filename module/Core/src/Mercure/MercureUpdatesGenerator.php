<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Mercure;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Symfony\Component\Mercure\Update;

use function Shlinkio\Shlink\Common\json_encode;
use function sprintf;

final class MercureUpdatesGenerator implements MercureUpdatesGeneratorInterface
{
    private const NEW_VISIT_TOPIC = 'https://shlink.io/new-visit';
    private const NEW_ORPHAN_VISIT_TOPIC = 'https://shlink.io/new-orphan-visit';

    public function __construct(
        private DataTransformerInterface $shortUrlTransformer,
        private DataTransformerInterface $orphanVisitTransformer,
    ) {
    }

    public function newVisitUpdate(Visit $visit): Update
    {
        return new Update(self::NEW_VISIT_TOPIC, json_encode([
            'shortUrl' => $this->shortUrlTransformer->transform($visit->getShortUrl()),
            'visit' => $visit,
        ]));
    }

    public function newOrphanVisitUpdate(Visit $visit): Update
    {
        return new Update(self::NEW_ORPHAN_VISIT_TOPIC, json_encode([
            'visit' => $this->orphanVisitTransformer->transform($visit),
        ]));
    }

    public function newShortUrlVisitUpdate(Visit $visit): Update
    {
        $shortUrl = $visit->getShortUrl();
        $topic = sprintf('%s/%s', self::NEW_VISIT_TOPIC, $shortUrl?->getShortCode());

        return new Update($topic, json_encode([
            'shortUrl' => $this->shortUrlTransformer->transform($shortUrl),
            'visit' => $visit,
        ]));
    }
}

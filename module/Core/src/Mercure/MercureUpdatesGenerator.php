<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Mercure;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Symfony\Component\Mercure\Update;

use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

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
        return new Update(self::NEW_VISIT_TOPIC, $this->serialize([
            'shortUrl' => $this->shortUrlTransformer->transform($visit->getShortUrl()),
            'visit' => $visit,
        ]));
    }

    public function newOrphanVisitUpdate(Visit $visit): Update
    {
        return new Update(self::NEW_ORPHAN_VISIT_TOPIC, $this->serialize([
            'visit' => $this->orphanVisitTransformer->transform($visit),
        ]));
    }

    public function newShortUrlVisitUpdate(Visit $visit): Update
    {
        $shortUrl = $visit->getShortUrl();
        $topic = sprintf('%s/%s', self::NEW_VISIT_TOPIC, $shortUrl?->getShortCode());

        return new Update($topic, $this->serialize([
            'shortUrl' => $this->shortUrlTransformer->transform($shortUrl),
            'visit' => $visit,
        ]));
    }

    private function serialize(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Mercure;

use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Symfony\Component\Mercure\Update;

use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class MercureUpdatesGenerator implements MercureUpdatesGeneratorInterface
{
    private const NEW_VISIT_TOPIC = 'https://shlink.io/new-visit';

    private ShortUrlDataTransformer $transformer;

    public function __construct(array $domainConfig)
    {
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
    }

    public function newVisitUpdate(Visit $visit): Update
    {
        return new Update(self::NEW_VISIT_TOPIC, $this->serialize([
            'shortUrl' => $this->transformer->transform($visit->getShortUrl()),
            'visit' => $visit,
        ]));
    }

    public function newShortUrlVisitUpdate(Visit $visit): Update
    {
        $shortUrl = $visit->getShortUrl();
        $topic = sprintf('%s/%s', self::NEW_VISIT_TOPIC, $shortUrl->getShortCode());

        return new Update($topic, $this->serialize([
            'shortUrl' => $this->transformer->transform($visit->getShortUrl()),
            'visit' => $visit,
        ]));
    }

    private function serialize(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Mercure;

use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Symfony\Component\Mercure\Update;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class MercureUpdatesGenerator implements MercureUpdatesGeneratorInterface
{
    private const NEW_VISIT_TOPIC = 'https://shlink.io/new_visit';

    private ShortUrlDataTransformer $transformer;

    public function __construct(array $domainConfig)
    {
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
    }

    public function newVisitUpdate(Visit $visit): Update
    {
        return new Update(self::NEW_VISIT_TOPIC, json_encode([
            'shortUrl' => $this->transformer->transform($visit->getShortUrl()),
            'visit' => $visit->jsonSerialize(),
        ], JSON_THROW_ON_ERROR));
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

final readonly class PublishingUpdatesGenerator implements PublishingUpdatesGeneratorInterface
{
    public function __construct(private DataTransformerInterface $shortUrlTransformer)
    {
    }

    public function newVisitUpdate(Visit $visit): Update
    {
        return Update::forTopicAndPayload(Topic::NEW_VISIT->value, [
            'shortUrl' => $this->shortUrlTransformer->transform($visit->shortUrl),
            'visit' => $visit->jsonSerialize(),
        ]);
    }

    public function newOrphanVisitUpdate(Visit $visit): Update
    {
        return Update::forTopicAndPayload(Topic::NEW_ORPHAN_VISIT->value, [
            'visit' => $visit->jsonSerialize(),
        ]);
    }

    public function newShortUrlVisitUpdate(Visit $visit): Update
    {
        $shortUrl = $visit->shortUrl;
        $topic = Topic::newShortUrlVisit($shortUrl?->getShortCode());

        return Update::forTopicAndPayload($topic, [
            'shortUrl' => $this->shortUrlTransformer->transform($shortUrl),
            'visit' => $visit->jsonSerialize(),
        ]);
    }

    public function newShortUrlUpdate(ShortUrl $shortUrl): Update
    {
        return Update::forTopicAndPayload(Topic::NEW_SHORT_URL->value, [
            'shortUrl' => $this->shortUrlTransformer->transform($shortUrl),
        ]);
    }
}

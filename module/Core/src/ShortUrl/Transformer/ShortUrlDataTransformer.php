<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;

use function array_map;

class ShortUrlDataTransformer implements DataTransformerInterface
{
    public function __construct(private readonly ShortUrlStringifierInterface $stringifier)
    {
    }

    /**
     * @param ShortUrl $shortUrl
     */
    public function transform($shortUrl): array // phpcs:ignore
    {
        return [
            'shortCode' => $shortUrl->getShortCode(),
            'shortUrl' => $this->stringifier->stringify($shortUrl),
            'longUrl' => $shortUrl->getLongUrl(),
            'deviceLongUrls' => $shortUrl->deviceLongUrls(),
            'dateCreated' => $shortUrl->getDateCreated()->toAtomString(),
            'tags' => array_map(static fn (Tag $tag) => $tag->__toString(), $shortUrl->getTags()->toArray()),
            'meta' => $this->buildMeta($shortUrl),
            'domain' => $shortUrl->getDomain(),
            'title' => $shortUrl->title(),
            'crawlable' => $shortUrl->crawlable(),
            'forwardQuery' => $shortUrl->forwardQuery(),
            'visitsSummary' => VisitsSummary::fromTotalAndNonBots(
                $shortUrl->getVisitsCount(),
                $shortUrl->nonBotVisitsCount(),
            ),

            // Deprecated
            'visitsCount' => $shortUrl->getVisitsCount(),
        ];
    }

    private function buildMeta(ShortUrl $shortUrl): array
    {
        $validSince = $shortUrl->getValidSince();
        $validUntil = $shortUrl->getValidUntil();
        $maxVisits = $shortUrl->getMaxVisits();

        return [
            'validSince' => $validSince?->toAtomString(),
            'validUntil' => $validUntil?->toAtomString(),
            'maxVisits' => $maxVisits,
        ];
    }
}

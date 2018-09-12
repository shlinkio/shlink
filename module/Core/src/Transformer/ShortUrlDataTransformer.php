<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Zend\Diactoros\Uri;

class ShortUrlDataTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    private $domainConfig;

    public function __construct(array $domainConfig)
    {
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param ShortUrl $value
     * @return array
     */
    public function transform($value): array
    {
        $dateCreated = $value->getDateCreated();
        $longUrl = $value->getLongUrl();
        $shortCode = $value->getShortCode();

        return [
            'shortCode' => $shortCode,
            'shortUrl' => $this->buildShortUrl($shortCode),
            'longUrl' => $longUrl,
            'dateCreated' => $dateCreated !== null ? $dateCreated->format(\DateTime::ATOM) : null,
            'visitsCount' => $value->getVisitsCount(),
            'tags' => \array_map([$this, 'serializeTag'], $value->getTags()->toArray()),

            // Deprecated
            'originalUrl' => $longUrl,
        ];
    }

    private function buildShortUrl(string $shortCode): string
    {
        return (string) (new Uri())->withPath($shortCode)
                                   ->withScheme($this->domainConfig['schema'] ?? 'http')
                                   ->withHost($this->domainConfig['hostname'] ?? '');
    }

    private function serializeTag(Tag $tag): string
    {
        return $tag->getName();
    }
}

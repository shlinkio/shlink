<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Util\ShortUrlBuilderTrait;
use function Functional\invoke;

class ShortUrlDataTransformer implements DataTransformerInterface
{
    use ShortUrlBuilderTrait;

    /** @var array */
    private $domainConfig;

    public function __construct(array $domainConfig)
    {
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param ShortUrl $value
     */
    public function transform($value): array
    {
        $dateCreated = $value->getDateCreated();
        $longUrl = $value->getLongUrl();
        $shortCode = $value->getShortCode();

        return [
            'shortCode' => $shortCode,
            'shortUrl' => $this->buildShortUrl($this->domainConfig, $shortCode),
            'longUrl' => $longUrl,
            'dateCreated' => $dateCreated !== null ? $dateCreated->toAtomString() : null,
            'visitsCount' => $value->getVisitsCount(),
            'tags' => invoke($value->getTags(), '__toString'),

            // Deprecated
            'originalUrl' => $longUrl,
        ];
    }
}

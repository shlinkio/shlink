<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Laminas\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlEdit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlServiceInterface
{
    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @param string[] $tags
     * @throws ShortUrlNotFoundException
     */
    public function setTagsByShortCode(ShortUrlIdentifier $identifier, array $tags = []): ShortUrl;

    /**
     * @throws ShortUrlNotFoundException
     * @throws InvalidUrlException
     */
    public function updateMetadataByShortCode(ShortUrlIdentifier $identifier, ShortUrlEdit $shortUrlEdit): ShortUrl;
}

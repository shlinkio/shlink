<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface DeleteShortUrlServiceInterface
{
    /**
     * @throws Exception\ShortUrlNotFoundException
     * @throws Exception\DeleteShortUrlException
     */
    public function deleteByShortCode(
        ShortUrlIdentifier $identifier,
        bool $ignoreThreshold = false,
        ?ApiKey $apiKey = null,
    ): void;
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlService implements ShortUrlServiceInterface
{
    public function __construct(
        private readonly ORM\EntityManagerInterface $em,
        private readonly ShortUrlResolverInterface $urlResolver,
        private readonly ShortUrlTitleResolutionHelperInterface $titleResolutionHelper,
        private readonly ShortUrlRelationResolverInterface $relationResolver,
    ) {
    }

    /**
     * @throws ShortUrlNotFoundException
     * @throws InvalidUrlException
     */
    public function updateShortUrl(
        ShortUrlIdentifier $identifier,
        ShortUrlEdition $shortUrlEdit,
        ?ApiKey $apiKey = null,
    ): ShortUrl {
        if ($shortUrlEdit->longUrlWasProvided()) {
            $shortUrlEdit = $this->titleResolutionHelper->processTitleAndValidateUrl($shortUrlEdit);
        }

        $shortUrl = $this->urlResolver->resolveShortUrl($identifier, $apiKey);
        $shortUrl->update($shortUrlEdit, $this->relationResolver);

        $this->em->flush();

        return $shortUrl;
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class ShortUrlService implements ShortUrlServiceInterface
{
    public function __construct(
        private ORM\EntityManagerInterface $em,
        private ShortUrlResolverInterface $urlResolver,
        private ShortUrlTitleResolutionHelperInterface $titleResolutionHelper,
        private ShortUrlRelationResolverInterface $relationResolver,
    ) {
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function updateShortUrl(
        ShortUrlIdentifier $identifier,
        ShortUrlEdition $shortUrlEdit,
        ApiKey|null $apiKey = null,
    ): ShortUrl {
        if ($shortUrlEdit->longUrlWasProvided()) {
            $shortUrlEdit = $this->titleResolutionHelper->processTitle($shortUrlEdit);
        }

        $shortUrl = $this->urlResolver->resolveShortUrl($identifier, $apiKey);
        $shortUrl->update($shortUrlEdit, $this->relationResolver);

        $this->em->flush();

        return $shortUrl;
    }
}

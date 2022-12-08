<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlService implements ShortUrlServiceInterface
{
    public function __construct(
        private readonly ORM\EntityManagerInterface $em,
        private readonly ShortUrlResolverInterface $urlResolver,
        private readonly ShortUrlTitleResolutionHelperInterface $titleResolutionHelper,
        private readonly ShortUrlRelationResolverInterface $relationResolver,
        private readonly UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $defaultDomain = $this->urlShortenerOptions->domain['hostname'] ?? '';
        $paginator = new Paginator(new ShortUrlRepositoryAdapter($repo, $params, $apiKey, $defaultDomain));
        $paginator->setMaxPerPage($params->itemsPerPage)
                  ->setCurrentPage($params->page);

        return $paginator;
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
            /** @var ShortUrlEdition $shortUrlEdit */
            $shortUrlEdit = $this->titleResolutionHelper->processTitleAndValidateUrl($shortUrlEdit);
        }

        $shortUrl = $this->urlResolver->resolveShortUrl($identifier, $apiKey);
        $shortUrl->update($shortUrlEdit, $this->relationResolver);

        $this->em->flush();

        return $shortUrl;
    }
}

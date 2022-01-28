<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlEdit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlService implements ShortUrlServiceInterface
{
    public function __construct(
        private ORM\EntityManagerInterface $em,
        private ShortUrlResolverInterface $urlResolver,
        private ShortUrlTitleResolutionHelperInterface $titleResolutionHelper,
        private ShortUrlRelationResolverInterface $relationResolver,
    ) {
    }

    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $paginator = new Paginator(new ShortUrlRepositoryAdapter($repo, $params, $apiKey));
        $paginator->setMaxPerPage($params->itemsPerPage())
                  ->setCurrentPage($params->page());

        return $paginator;
    }

    /**
     * @throws ShortUrlNotFoundException
     * @throws InvalidUrlException
     */
    public function updateShortUrl(
        ShortUrlIdentifier $identifier,
        ShortUrlEdit $shortUrlEdit,
        ?ApiKey $apiKey = null,
    ): ShortUrl {
        if ($shortUrlEdit->longUrlWasProvided()) {
            /** @var ShortUrlEdit $shortUrlEdit */
            $shortUrlEdit = $this->titleResolutionHelper->processTitleAndValidateUrl($shortUrlEdit);
        }

        $shortUrl = $this->urlResolver->resolveShortUrl($identifier, $apiKey);
        $shortUrl->update($shortUrlEdit, $this->relationResolver);

        $this->em->flush();

        return $shortUrl;
    }
}

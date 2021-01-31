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
use Shlinkio\Shlink\Core\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlService implements ShortUrlServiceInterface
{
    private ORM\EntityManagerInterface $em;
    private ShortUrlResolverInterface $urlResolver;
    private UrlValidatorInterface $urlValidator;
    private ShortUrlRelationResolverInterface $relationResolver;

    public function __construct(
        ORM\EntityManagerInterface $em,
        ShortUrlResolverInterface $urlResolver,
        UrlValidatorInterface $urlValidator,
        ShortUrlRelationResolverInterface $relationResolver
    ) {
        $this->em = $em;
        $this->urlResolver = $urlResolver;
        $this->urlValidator = $urlValidator;
        $this->relationResolver = $relationResolver;
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
        ?ApiKey $apiKey = null
    ): ShortUrl {
        if ($shortUrlEdit->hasLongUrl()) {
            $this->urlValidator->validateUrl($shortUrlEdit->longUrl(), $shortUrlEdit->doValidateUrl());
        }

        $shortUrl = $this->urlResolver->resolveShortUrl($identifier, $apiKey);
        $shortUrl->update($shortUrlEdit, $this->relationResolver);

        $this->em->flush();

        return $shortUrl;
    }
}

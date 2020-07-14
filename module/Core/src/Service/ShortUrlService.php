<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Laminas\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlEdit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;

class ShortUrlService implements ShortUrlServiceInterface
{
    use TagManagerTrait;

    private ORM\EntityManagerInterface $em;
    private ShortUrlResolverInterface $urlResolver;
    private UrlValidatorInterface $urlValidator;

    public function __construct(
        ORM\EntityManagerInterface $em,
        ShortUrlResolverInterface $urlResolver,
        UrlValidatorInterface $urlValidator
    ) {
        $this->em = $em;
        $this->urlResolver = $urlResolver;
        $this->urlValidator = $urlValidator;
    }

    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params): Paginator
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $paginator = new Paginator(new ShortUrlRepositoryAdapter($repo, $params));
        $paginator->setItemCountPerPage($params->itemsPerPage())
                  ->setCurrentPageNumber($params->page());

        return $paginator;
    }

    /**
     * @param string[] $tags
     * @throws ShortUrlNotFoundException
     */
    public function setTagsByShortCode(ShortUrlIdentifier $identifier, array $tags = []): ShortUrl
    {
        $shortUrl = $this->urlResolver->resolveShortUrl($identifier);
        $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));

        $this->em->flush();

        return $shortUrl;
    }

    /**
     * @throws ShortUrlNotFoundException
     * @throws InvalidUrlException
     */
    public function updateMetadataByShortCode(ShortUrlIdentifier $identifier, ShortUrlEdit $shortUrlEdit): ShortUrl
    {
        if ($shortUrlEdit->hasLongUrl()) {
            $this->urlValidator->validateUrl($shortUrlEdit->longUrl());
        }

        $shortUrl = $this->urlResolver->resolveShortUrl($identifier);
        $shortUrl->update($shortUrlEdit);

        $this->em->flush();

        return $shortUrl;
    }
}

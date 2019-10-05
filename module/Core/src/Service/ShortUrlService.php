<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrl\FindShortCodeTrait;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Zend\Paginator\Paginator;

class ShortUrlService implements ShortUrlServiceInterface
{
    use FindShortCodeTrait;
    use TagManagerTrait;

    /** @var ORM\EntityManagerInterface */
    private $em;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param string[] $tags
     * @param array|string|null $orderBy
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(int $page = 1, ?string $searchQuery = null, array $tags = [], $orderBy = null)
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $paginator = new Paginator(new ShortUrlRepositoryAdapter($repo, $searchQuery, $tags, $orderBy));
        $paginator->setItemCountPerPage(ShortUrlRepositoryAdapter::ITEMS_PER_PAGE)
                  ->setCurrentPageNumber($page);

        return $paginator;
    }

    /**
     * @param string[] $tags
     * @throws InvalidShortCodeException
     */
    public function setTagsByShortCode(string $shortCode, array $tags = []): ShortUrl
    {
        $shortUrl = $this->findByShortCode($this->em, $shortCode);
        $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));
        $this->em->flush();

        return $shortUrl;
    }

    /**
     * @throws InvalidShortCodeException
     */
    public function updateMetadataByShortCode(string $shortCode, ShortUrlMeta $shortUrlMeta): ShortUrl
    {
        $shortUrl = $this->findByShortCode($this->em, $shortCode);
        $shortUrl->updateMeta($shortUrlMeta);

        /** @var ORM\EntityManager $em */
        $em = $this->em;
        $em->flush($shortUrl);

        return $shortUrl;
    }
}

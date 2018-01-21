<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Shlinkio\Shlink\Common\Paginator\Adapter\PaginableRepositoryAdapter;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Zend\Paginator\Paginator;

class ShortUrlService implements ShortUrlServiceInterface
{
    use TagManagerTrait;

    /**
     * @var ORM\EntityManagerInterface
     */
    private $em;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $page
     * @param string $searchQuery
     * @param array $tags
     * @param null $orderBy
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls($page = 1, $searchQuery = null, array $tags = [], $orderBy = null)
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $paginator = new Paginator(new PaginableRepositoryAdapter($repo, $searchQuery, $tags, $orderBy));
        $paginator->setItemCountPerPage(PaginableRepositoryAdapter::ITEMS_PER_PAGE)
                  ->setCurrentPageNumber($page);

        return $paginator;
    }

    /**
     * @param string $shortCode
     * @param string[] $tags
     * @return ShortUrl
     * @throws InvalidShortCodeException
     */
    public function setTagsByShortCode(string $shortCode, array $tags = []): ShortUrl
    {
        $shortUrl = $this->findByShortCode($shortCode);
        $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));
        $this->em->flush();

        return $shortUrl;
    }

    /**
     * @param string $shortCode
     * @param ShortUrlMeta $shortCodeMeta
     * @return ShortUrl
     * @throws InvalidShortCodeException
     */
    public function updateMetadataByShortCode(string $shortCode, ShortUrlMeta $shortCodeMeta): ShortUrl
    {
        $shortUrl = $this->findByShortCode($shortCode);
        if ($shortCodeMeta->hasValidSince()) {
            $shortUrl->setValidSince($shortCodeMeta->getValidSince());
        }
        if ($shortCodeMeta->hasValidUntil()) {
            $shortUrl->setValidUntil($shortCodeMeta->getValidUntil());
        }
        if ($shortCodeMeta->hasMaxVisits()) {
            $shortUrl->setMaxVisits($shortCodeMeta->getMaxVisits());
        }

        /** @var ORM\EntityManager $em */
        $em = $this->em;
        $em->flush($shortUrl);
        return $shortUrl;
    }

    /**
     * @param string $shortCode
     * @return ShortUrl
     * @throws InvalidShortCodeException
     */
    private function findByShortCode(string $shortCode): ShortUrl
    {
        /** @var ShortUrl|null $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);
        if ($shortUrl === null) {
            throw InvalidShortCodeException::fromNotFoundShortCode($shortCode);
        }

        return $shortUrl;
    }
}

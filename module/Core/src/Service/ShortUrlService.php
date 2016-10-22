<?php
namespace Shlinkio\Shlink\Core\Service;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Paginator\Adapter\PaginableRepositoryAdapter;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Zend\Paginator\Paginator;

class ShortUrlService implements ShortUrlServiceInterface
{
    use TagManagerTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * ShortUrlService constructor.
     * @param EntityManagerInterface $em
     *
     * @Inject({"em"})
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $page
     * @param string $searchQuery
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls($page = 1, $searchQuery = null)
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $paginator = new Paginator(new PaginableRepositoryAdapter($repo, $searchQuery));
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
    public function setTagsByShortCode($shortCode, array $tags = [])
    {
        /** @var ShortUrl $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);
        if (! isset($shortUrl)) {
            throw InvalidShortCodeException::fromNotFoundShortCode($shortCode);
        }

        $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));
        $this->em->flush();

        return $shortUrl;
    }
}

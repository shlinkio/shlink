<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Acelaya\UrlShortener\Repository\ShortUrlRepository;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Paginator\Adapter\PaginableRepositoryAdapter;
use Zend\Paginator\Paginator;

class ShortUrlService implements ShortUrlServiceInterface
{
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
     * @return Paginator|ShortUrl[]
     */
    public function listShortUrls($page = 1)
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $paginator = new Paginator(new PaginableRepositoryAdapter($repo));
        $paginator->setItemCountPerPage(PaginableRepositoryAdapter::ITEMS_PER_PAGE)
                  ->setCurrentPageNumber($page);

        return $paginator;
    }
}

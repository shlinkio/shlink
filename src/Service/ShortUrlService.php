<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;

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
     * @return ShortUrl[]
     */
    public function listShortUrls()
    {
        return $this->em->getRepository(ShortUrl::class)->findAll();
    }
}

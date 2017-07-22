<?php
namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

class VisitService implements VisitServiceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return Visit[]
     */
    public function getUnlocatedVisits()
    {
        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        return $repo->findUnlocatedVisits();
    }

    /**
     * @param Visit $visit
     */
    public function saveVisit(Visit $visit)
    {
        $this->em->persist($visit);
        $this->em->flush();
    }
}

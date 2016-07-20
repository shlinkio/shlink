<?php
namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitRepositoryInterface extends ObjectRepository
{
    /**
     * @return Visit[]
     */
    public function findUnlocatedVisits();
}

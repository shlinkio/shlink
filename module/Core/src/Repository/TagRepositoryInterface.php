<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Common\Persistence\ObjectRepository;

interface TagRepositoryInterface extends ObjectRepository
{
    /**
     * Delete the tags identified by provided names
     *
     * @param array $names
     * @return int The number of affected entries
     */
    public function deleteByName(array $names): int;
}

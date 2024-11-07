<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;

/**
 * @template T of object
 * @extends ObjectRepository<T>
 */
interface EntityRepositoryInterface extends ObjectRepository
{
    /**
     * @todo This should be part of ObjectRepository, so adding here until that interface defines it.
     *       EntityRepository already implements the method, so classes extending it won't have to add anything.
     */
    public function count(array $criteria = []): int;
}

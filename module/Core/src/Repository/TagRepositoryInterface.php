<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Common\Persistence\ObjectRepository;

interface TagRepositoryInterface extends ObjectRepository
{
    public function deleteByName(array $names): int;
}

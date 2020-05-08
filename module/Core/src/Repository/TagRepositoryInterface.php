<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;

interface TagRepositoryInterface extends ObjectRepository
{
    public function deleteByName(array $names): int;

    /**
     * @return TagInfo[]
     */
    public function findTagsWithInfo(): array;
}

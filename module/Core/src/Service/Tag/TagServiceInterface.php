<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\Tag;

use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;

interface TagServiceInterface
{
    /**
     * @return Tag[]
     */
    public function listTags(): array;

    /**
     * @param string[] $tagNames
     */
    public function deleteTags(array $tagNames): void;

    /**
     * @param string[] $tagNames
     * @return Collection|Tag[]
     */
    public function createTags(array $tagNames): Collection;

    /**
     * @throws TagNotFoundException
     */
    public function renameTag(string $oldName, string $newName): Tag;
}

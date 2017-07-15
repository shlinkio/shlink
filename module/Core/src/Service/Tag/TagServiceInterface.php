<?php
namespace Shlinkio\Shlink\Core\Service\Tag;

use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;

interface TagServiceInterface
{
    /**
     * @return Tag[]
     */
    public function listTags();

    /**
     * @param string[] $tagNames
     * @return void
     */
    public function deleteTags(array $tagNames);

    /**
     * Provided a list of tag names, creates all that do not exist yet
     *
     * @param string[] $tagNames
     * @return Collection|Tag[]
     */
    public function createTags(array $tagNames);

    /**
     * @param string $oldName
     * @param string $newName
     * @return Tag
     * @throws EntityDoesNotExistException
     */
    public function renameTag($oldName, $newName);
}

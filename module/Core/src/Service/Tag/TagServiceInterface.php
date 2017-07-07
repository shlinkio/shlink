<?php
namespace Shlinkio\Shlink\Core\Service\Tag;

use Shlinkio\Shlink\Core\Entity\Tag;

interface TagServiceInterface
{
    /**
     * @return Tag[]
     */
    public function listTags();
}

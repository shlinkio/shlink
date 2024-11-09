<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\Repository\EntityRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/** @extends EntityRepositoryInterface<Tag> */
interface TagRepositoryInterface extends EntityRepositoryInterface, EntitySpecificationRepositoryInterface
{
    public function deleteByName(array $names): int;

    /**
     * @return TagInfo[]
     */
    public function findTagsWithInfo(TagsListFiltering|null $filtering = null): array;

    public function tagExists(string $tag, ApiKey|null $apiKey = null): bool;
}

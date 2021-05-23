<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface TagRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    public function deleteByName(array $names): int;

    /**
     * @return TagInfo[]
     */
    public function findTagsWithInfo(?ApiKey $apiKey = null): array;

    public function tagExists(string $tag, ?ApiKey $apiKey = null): bool;
}

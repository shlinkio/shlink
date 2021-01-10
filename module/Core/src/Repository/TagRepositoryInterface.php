<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\EntitySpecificationRepositoryInterface;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface TagRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    public function deleteByName(array $names): int;

    /**
     * @return TagInfo[]
     */
    public function findTagsWithInfo(?Specification $spec = null): array;

    public function tagExists(string $tag, ?ApiKey $apiKey = null): bool;
}

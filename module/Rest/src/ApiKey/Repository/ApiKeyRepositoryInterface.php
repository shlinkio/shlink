<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\Repository\EntityRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/**
 * @extends EntityRepositoryInterface<ApiKey>
 */
interface ApiKeyRepositoryInterface extends EntityRepositoryInterface, EntitySpecificationRepositoryInterface
{
    /**
     * Will create provided API key only if there's no API keys yet
     */
    public function createInitialApiKey(string $apiKey): ApiKey|null;
}

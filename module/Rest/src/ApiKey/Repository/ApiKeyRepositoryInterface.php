<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;

interface ApiKeyRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    /**
     * Will create provided API key only if there's no API keys yet
     */
    public function createInitialApiKey(string $apiKey): void;
}

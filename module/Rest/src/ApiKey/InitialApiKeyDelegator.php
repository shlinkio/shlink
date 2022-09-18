<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey;

use Doctrine\ORM\EntityManager;
use Mezzio\Application;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Rest\ApiKey\Repository\ApiKeyRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class InitialApiKeyDelegator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): Application
    {
        $initialApiKey = $container->get('config')['initial_api_key'] ?? null;
        if (! empty($initialApiKey)) {
            $this->createInitialApiKey($initialApiKey, $container);
        }

        return $callback();
    }

    private function createInitialApiKey(string $initialApiKey, ContainerInterface $container): void
    {
        /** @var ApiKeyRepositoryInterface $repo */
        $repo = $container->get(EntityManager::class)->getRepository(ApiKey::class);
        $repo->createInitialApiKey($initialApiKey);
    }
}

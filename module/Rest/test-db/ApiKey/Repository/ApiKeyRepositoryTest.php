<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Rest\ApiKey\Repository;

use Shlinkio\Shlink\Rest\ApiKey\Repository\ApiKeyRepository;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class ApiKeyRepositoryTest extends DatabaseTestCase
{
    private ApiKeyRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ApiKey::class);
    }

    /** @test */
    public function initialApiKeyIsCreatedOnlyOfNoApiKeysExistYet(): void
    {
        self::assertCount(0, $this->repo->findAll());
        $this->repo->createInitialApiKey('initial_value');
        self::assertCount(1, $this->repo->findAll());
        self::assertCount(1, $this->repo->findBy(['key' => 'initial_value']));
        $this->repo->createInitialApiKey('another_one');
        self::assertCount(1, $this->repo->findAll());
        self::assertCount(0, $this->repo->findBy(['key' => 'another_one']));
    }
}

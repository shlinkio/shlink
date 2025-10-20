<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Rest\ApiKey\Repository;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
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

    #[Test]
    public function initialApiKeyIsCreatedOnlyOfNoApiKeysExistYet(): void
    {
        self::assertCount(0, $this->repo->findAll());
        self::assertNotNull($this->repo->createInitialApiKey('initial_value'));
        self::assertCount(1, $this->repo->findAll());
        self::assertCount(1, $this->repo->findBy(['key' => ApiKey::hashKey('initial_value')]));
        self::assertNull($this->repo->createInitialApiKey('another_one'));
        self::assertCount(1, $this->repo->findAll());
        self::assertCount(0, $this->repo->findBy(['key' => ApiKey::hashKey('another_one')]));
    }

    #[Test]
    public function nameExistsReturnsExpectedResult(): void
    {
        $this->getEntityManager()->persist(ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'foo')));
        $this->getEntityManager()->flush();

        self::assertTrue($this->repo->nameExists('foo'));
        self::assertFalse($this->repo->nameExists('bar'));
    }

    #[Test]
    public function deleteByNameReturnsExpectedValue(): void
    {
        $this->getEntityManager()->persist(ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'foo')));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        self::assertEquals(0, $this->repo->deleteByName('invalid'));
        self::assertEquals(1, $this->repo->deleteByName('foo'));

        // Verify the API key has been deleted
        self::assertNull($this->repo->findOneBy(['name' => 'foo']));
    }
}

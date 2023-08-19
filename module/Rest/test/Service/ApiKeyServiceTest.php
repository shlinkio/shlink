<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;

class ApiKeyServiceTest extends TestCase
{
    private ApiKeyService $service;
    private MockObject & EntityManager $em;
    private MockObject & EntityRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->service = new ApiKeyService($this->em);
    }

    /**
     * @param RoleDefinition[] $roles
     */
    #[Test, DataProvider('provideCreationDate')]
    public function apiKeyIsProperlyCreated(?Chronos $date, ?string $name, array $roles): void
    {
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(ApiKey::class));

        $key = $this->service->create($date, $name, ...$roles);

        self::assertEquals($date, $key->getExpirationDate());
        self::assertEquals($name, $key->name());
        foreach ($roles as $roleDefinition) {
            self::assertTrue($key->hasRole($roleDefinition->role));
        }
    }

    public static function provideCreationDate(): iterable
    {
        $domain = Domain::withAuthority('');
        $domain->setId('123');

        yield 'no expiration date or name' => [null, null, []];
        yield 'expiration date' => [Chronos::parse('2030-01-01'), null, []];
        yield 'roles' => [null, null, [
            RoleDefinition::forDomain($domain),
            RoleDefinition::forAuthoredShortUrls(),
        ]];
        yield 'single name' => [null, 'Alice', []];
        yield 'multi-word name' => [null, 'Alice and Bob', []];
        yield 'empty name' => [null, '', []];
    }

    #[Test, DataProvider('provideInvalidApiKeys')]
    public function checkReturnsFalseForInvalidApiKeys(?ApiKey $invalidKey): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->with(['key' => '12345'])->willReturn($invalidKey);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->check('12345');

        self::assertFalse($result->isValid());
        self::assertSame($invalidKey, $result->apiKey);
    }

    public static function provideInvalidApiKeys(): iterable
    {
        yield 'non-existent api key' => [null];
        yield 'disabled api key' => [ApiKey::create()->disable()];
        yield 'expired api key' => [ApiKey::fromMeta(ApiKeyMeta::withExpirationDate(Chronos::now()->subDays(1)))];
    }

    #[Test]
    public function checkReturnsTrueWhenConditionsAreFavorable(): void
    {
        $apiKey = ApiKey::create();

        $this->repo->expects($this->once())->method('findOneBy')->with(['key' => '12345'])->willReturn($apiKey);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->check('12345');

        self::assertTrue($result->isValid());
        self::assertSame($apiKey, $result->apiKey);
    }

    #[Test]
    public function disableThrowsExceptionWhenNoApiKeyIsFound(): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->with(['key' => '12345'])->willReturn(null);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $this->expectException(InvalidArgumentException::class);

        $this->service->disable('12345');
    }

    #[Test]
    public function disableReturnsDisabledApiKeyWhenFound(): void
    {
        $key = ApiKey::create();
        $this->repo->expects($this->once())->method('findOneBy')->with(['key' => '12345'])->willReturn($key);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);
        $this->em->expects($this->once())->method('flush');

        self::assertTrue($key->isEnabled());
        $returnedKey = $this->service->disable('12345');
        self::assertFalse($key->isEnabled());
        self::assertSame($key, $returnedKey);
    }

    #[Test]
    public function listFindsAllApiKeys(): void
    {
        $expectedApiKeys = [ApiKey::create(), ApiKey::create(), ApiKey::create()];

        $this->repo->expects($this->once())->method('findBy')->with([])->willReturn($expectedApiKeys);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->listKeys();

        self::assertEquals($expectedApiKeys, $result);
    }

    #[Test]
    public function listEnabledFindsOnlyEnabledApiKeys(): void
    {
        $expectedApiKeys = [ApiKey::create(), ApiKey::create(), ApiKey::create()];

        $this->repo->expects($this->once())->method('findBy')->with(['enabled' => true])->willReturn($expectedApiKeys);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->listKeys(true);

        self::assertEquals($expectedApiKeys, $result);
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
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
    private MockObject $em;
    private MockObject $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->service = new ApiKeyService($this->em);
    }

    /**
     * @test
     * @dataProvider provideCreationDate
     * @param RoleDefinition[] $roles
     */
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

    public function provideCreationDate(): iterable
    {
        yield 'no expiration date or name' => [null, null, []];
        yield 'expiration date' => [Chronos::parse('2030-01-01'), null, []];
        yield 'roles' => [null, null, [
            RoleDefinition::forDomain(Domain::withAuthority('')->setId('123')),
            RoleDefinition::forAuthoredShortUrls(),
        ]];
        yield 'single name' => [null, 'Alice', []];
        yield 'multi-word name' => [null, 'Alice and Bob', []];
        yield 'empty name' => [null, '', []];
    }

    /**
     * @test
     * @dataProvider provideInvalidApiKeys
     */
    public function checkReturnsFalseForInvalidApiKeys(?ApiKey $invalidKey): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->with(['key' => '12345'])->willReturn($invalidKey);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->check('12345');

        self::assertFalse($result->isValid());
        self::assertSame($invalidKey, $result->apiKey);
    }

    public function provideInvalidApiKeys(): iterable
    {
        yield 'non-existent api key' => [null];
        yield 'disabled api key' => [ApiKey::create()->disable()];
        yield 'expired api key' => [ApiKey::fromMeta(ApiKeyMeta::withExpirationDate(Chronos::now()->subDay()))];
    }

    /** @test */
    public function checkReturnsTrueWhenConditionsAreFavorable(): void
    {
        $apiKey = ApiKey::create();

        $this->repo->expects($this->once())->method('findOneBy')->with(['key' => '12345'])->willReturn($apiKey);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->check('12345');

        self::assertTrue($result->isValid());
        self::assertSame($apiKey, $result->apiKey);
    }

    /** @test */
    public function disableThrowsExceptionWhenNoApiKeyIsFound(): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->with(['key' => '12345'])->willReturn(null);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $this->expectException(InvalidArgumentException::class);

        $this->service->disable('12345');
    }

    /** @test */
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

    /** @test */
    public function listFindsAllApiKeys(): void
    {
        $expectedApiKeys = [ApiKey::create(), ApiKey::create(), ApiKey::create()];

        $this->repo->expects($this->once())->method('findBy')->with([])->willReturn($expectedApiKeys);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->listKeys();

        self::assertEquals($expectedApiKeys, $result);
    }

    /** @test */
    public function listEnabledFindsOnlyEnabledApiKeys(): void
    {
        $expectedApiKeys = [ApiKey::create(), ApiKey::create(), ApiKey::create()];

        $this->repo->expects($this->once())->method('findBy')->with(['enabled' => true])->willReturn($expectedApiKeys);
        $this->em->method('getRepository')->with(ApiKey::class)->willReturn($this->repo);

        $result = $this->service->listKeys(true);

        self::assertEquals($expectedApiKeys, $result);
    }
}

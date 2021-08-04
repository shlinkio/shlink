<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;

class ApiKeyServiceTest extends TestCase
{
    use ProphecyTrait;

    private ApiKeyService $service;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->service = new ApiKeyService($this->em->reveal());
    }

    /**
     * @test
     * @dataProvider provideCreationDate
     * @param RoleDefinition[] $roles
     */
    public function apiKeyIsProperlyCreated(?Chronos $date, ?string $name, array $roles): void
    {
        $this->em->flush()->shouldBeCalledOnce();
        $this->em->persist(Argument::type(ApiKey::class))->shouldBeCalledOnce();

        $key = $this->service->create($date, $name, ...$roles);

        self::assertEquals($date, $key->getExpirationDate());
        self::assertEquals($name, $key->name());
        foreach ($roles as $roleDefinition) {
            self::assertTrue($key->hasRole($roleDefinition->roleName()));
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
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn($invalidKey)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $result = $this->service->check('12345');

        self::assertFalse($result->isValid());
        self::assertSame($invalidKey, $result->apiKey());
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

        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn($apiKey)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $result = $this->service->check('12345');

        self::assertTrue($result->isValid());
        self::assertSame($apiKey, $result->apiKey());
    }

    /** @test */
    public function disableThrowsExceptionWhenNoApiKeyIsFound(): void
    {
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn(null)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->expectException(InvalidArgumentException::class);

        $this->service->disable('12345');
    }

    /** @test */
    public function disableReturnsDisabledApiKeyWhenFound(): void
    {
        $key = ApiKey::create();
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn($key)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->em->flush()->shouldBeCalledOnce();

        self::assertTrue($key->isEnabled());
        $returnedKey = $this->service->disable('12345');
        self::assertFalse($key->isEnabled());
        self::assertSame($key, $returnedKey);
    }

    /** @test */
    public function listFindsAllApiKeys(): void
    {
        $expectedApiKeys = [ApiKey::create(), ApiKey::create(), ApiKey::create()];

        $repo = $this->prophesize(EntityRepository::class);
        $repo->findBy([])->willReturn($expectedApiKeys)
                         ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $result = $this->service->listKeys();

        self::assertEquals($expectedApiKeys, $result);
    }

    /** @test */
    public function listEnabledFindsOnlyEnabledApiKeys(): void
    {
        $expectedApiKeys = [ApiKey::create(), ApiKey::create(), ApiKey::create()];

        $repo = $this->prophesize(EntityRepository::class);
        $repo->findBy(['enabled' => true])->willReturn($expectedApiKeys)
                                          ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $result = $this->service->listKeys(true);

        self::assertEquals($expectedApiKeys, $result);
    }
}

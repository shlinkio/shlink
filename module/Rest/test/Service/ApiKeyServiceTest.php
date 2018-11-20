<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;

class ApiKeyServiceTest extends TestCase
{
    /** @var ApiKeyService */
    protected $service;
    /** @var ObjectProphecy */
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->service = new ApiKeyService($this->em->reveal());
    }

    /**
     * @test
     */
    public function keyIsProperlyCreated()
    {
        $this->em->flush()->shouldBeCalledOnce();
        $this->em->persist(Argument::type(ApiKey::class))->shouldBeCalledOnce();

        $key = $this->service->create();
        $this->assertNull($key->getExpirationDate());
    }

    /**
     * @test
     */
    public function keyIsProperlyCreatedWithExpirationDate()
    {
        $this->em->flush()->shouldBeCalledOnce();
        $this->em->persist(Argument::type(ApiKey::class))->shouldBeCalledOnce();

        $date = Chronos::parse('2030-01-01');
        $key = $this->service->create($date);
        $this->assertSame($date, $key->getExpirationDate());
    }

    /**
     * @test
     */
    public function checkReturnsFalseWhenKeyIsInvalid()
    {
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn(null)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->assertFalse($this->service->check('12345'));
    }

    /**
     * @test
     */
    public function checkReturnsFalseWhenKeyIsDisabled()
    {
        $key = new ApiKey();
        $key->disable();
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn($key)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->assertFalse($this->service->check('12345'));
    }

    /**
     * @test
     */
    public function checkReturnsFalseWhenKeyIsExpired()
    {
        $key = new ApiKey(Chronos::now()->subDay());
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn($key)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->assertFalse($this->service->check('12345'));
    }

    /**
     * @test
     */
    public function checkReturnsTrueWhenConditionsAreFavorable()
    {
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn(new ApiKey())
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->assertTrue($this->service->check('12345'));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Common\Exception\InvalidArgumentException
     */
    public function disableThrowsExceptionWhenNoTokenIsFound()
    {
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn(null)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->service->disable('12345');
    }

    /**
     * @test
     */
    public function disableReturnsDisabledKeyWhenFOund()
    {
        $key = new ApiKey();
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn($key)
                                            ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->em->flush()->shouldBeCalledOnce();

        $this->assertTrue($key->isEnabled());
        $returnedKey = $this->service->disable('12345');
        $this->assertFalse($key->isEnabled());
        $this->assertSame($key, $returnedKey);
    }

    /**
     * @test
     */
    public function listFindsAllApiKeys()
    {
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findBy([])->willReturn([])
                         ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->service->listKeys();
    }

    /**
     * @test
     */
    public function listEnabledFindsOnlyEnabledApiKeys()
    {
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findBy(['enabled' => true])->willReturn([])
                                          ->shouldBeCalledOnce();
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->service->listKeys(true);
    }
}

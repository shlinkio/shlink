<?php
namespace ShlinkioTest\Shlink\Rest\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;

class ApiKeyServiceTest extends TestCase
{
    /**
     * @var ApiKeyService
     */
    protected $service;
    /**
     * @var ObjectProphecy
     */
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
        $this->em->flush()->shouldBeCalledTimes(1);
        $this->em->persist(Argument::type(ApiKey::class))->shouldBeCalledTimes(1);

        $key = $this->service->create();
        $this->assertNull($key->getExpirationDate());
    }

    /**
     * @test
     */
    public function keyIsProperlyCreatedWithExpirationDate()
    {
        $this->em->flush()->shouldBeCalledTimes(1);
        $this->em->persist(Argument::type(ApiKey::class))->shouldBeCalledTimes(1);

        $date = new \DateTime('2030-01-01');
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
                                            ->shouldBeCalledTimes(1);
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
                                            ->shouldBeCalledTimes(1);
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->assertFalse($this->service->check('12345'));
    }

    /**
     * @test
     */
    public function checkReturnsFalseWhenKeyIsExpired()
    {
        $key = new ApiKey();
        $key->setExpirationDate((new \DateTime())->sub(new \DateInterval('P1D')));
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['key' => '12345'])->willReturn($key)
                                            ->shouldBeCalledTimes(1);
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
                                            ->shouldBeCalledTimes(1);
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
                                            ->shouldBeCalledTimes(1);
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
                                            ->shouldBeCalledTimes(1);
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->em->flush()->shouldBeCalledTimes(1);

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
                         ->shouldBeCalledTimes(1);
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
                                          ->shouldBeCalledTimes(1);
        $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $this->service->listKeys(true);
    }
}

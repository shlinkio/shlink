<?php
namespace ShlinkioTest\Shlink\Rest\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\RestToken;
use Shlinkio\Shlink\Rest\Service\RestTokenService;

class RestTokenServiceTest extends TestCase
{
    /**
     * @var RestTokenService
     */
    protected $service;
    /**
     * @var ObjectProphecy
     */
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->service = new RestTokenService($this->em->reveal(), [
            'username' => 'foo',
            'password' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function tokenIsCreatedIfCredentialsAreCorrect()
    {
        $this->em->persist(Argument::type(RestToken::class))->shouldBeCalledTimes(1);
        $this->em->flush()->shouldBeCalledTimes(1);

        $token = $this->service->createToken('foo', 'bar');
        $this->assertInstanceOf(RestToken::class, $token);
        $this->assertFalse($token->isExpired());
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Rest\Exception\AuthenticationException
     */
    public function exceptionIsThrownWhileCreatingTokenWithWrongCredentials()
    {
        $this->service->createToken('foo', 'wrong');
    }

    /**
     * @test
     */
    public function restTokenIsReturnedFromTokenString()
    {
        $authToken = 'ABC-abc';
        $theToken = new RestToken();
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['token' => $authToken])->willReturn($theToken)->shouldBeCalledTimes(1);
        $this->em->getRepository(RestToken::class)->willReturn($repo->reveal())->shouldBeCalledTimes(1);

        $this->assertSame($theToken, $this->service->getByToken($authToken));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Common\Exception\InvalidArgumentException
     */
    public function exceptionIsThrownWhenRequestingWrongToken()
    {
        $authToken = 'ABC-abc';
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['token' => $authToken])->willReturn(null)->shouldBeCalledTimes(1);
        $this->em->getRepository(RestToken::class)->willReturn($repo->reveal())->shouldBeCalledTimes(1);

        $this->service->getByToken($authToken);
    }

    /**
     * @test
     */
    public function updateExpirationFlushesEntityManager()
    {
        $token = $this->prophesize(RestToken::class);
        $token->updateExpiration()->shouldBeCalledTimes(1);
        $this->em->flush()->shouldBeCalledTimes(1);

        $this->service->updateExpiration($token->reveal());
    }
}

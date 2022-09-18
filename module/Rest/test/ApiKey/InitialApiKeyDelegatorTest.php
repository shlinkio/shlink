<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\ApiKey;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mezzio\Application;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Rest\ApiKey\InitialApiKeyDelegator;
use Shlinkio\Shlink\Rest\ApiKey\Repository\ApiKeyRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class InitialApiKeyDelegatorTest extends TestCase
{
    use ProphecyTrait;

    private InitialApiKeyDelegator $delegator;
    private ObjectProphecy $container;

    protected function setUp(): void
    {
        $this->delegator = new InitialApiKeyDelegator();
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    /**
     * @test
     * @dataProvider provideConfigs
     */
    public function apiKeyIsInitializedWhenAppropriate(array $config, int $expectedCalls): void
    {
        $app = $this->prophesize(Application::class)->reveal();
        $apiKeyRepo = $this->prophesize(ApiKeyRepositoryInterface::class);
        $em = $this->prophesize(EntityManagerInterface::class);

        $getConfig = $this->container->get('config')->willReturn($config);
        $getRepo = $em->getRepository(ApiKey::class)->willReturn($apiKeyRepo->reveal());
        $getEm = $this->container->get(EntityManager::class)->willReturn($em->reveal());

        $result = ($this->delegator)($this->container->reveal(), '', fn () => $app);

        self::assertSame($result, $app);
        $getConfig->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledTimes($expectedCalls);
        $getEm->shouldHaveBeenCalledTimes($expectedCalls);
        $apiKeyRepo->createInitialApiKey(Argument::any())->shouldHaveBeenCalledTimes($expectedCalls);
    }

    public function provideConfigs(): iterable
    {
        yield 'no api key' => [[], 0];
        yield 'null api key' => [['initial_api_key' => null], 0];
        yield 'empty api key' => [['initial_api_key' => ''], 0];
        yield 'valid api key' => [['initial_api_key' => 'the_initial_key'], 1];
    }
}

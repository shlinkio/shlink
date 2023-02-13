<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\ApiKey;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mezzio\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Rest\ApiKey\InitialApiKeyDelegator;
use Shlinkio\Shlink\Rest\ApiKey\Repository\ApiKeyRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class InitialApiKeyDelegatorTest extends TestCase
{
    private InitialApiKeyDelegator $delegator;
    private MockObject & ContainerInterface $container;

    protected function setUp(): void
    {
        $this->delegator = new InitialApiKeyDelegator();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    #[Test, DataProvider('provideConfigs')]
    public function apiKeyIsInitializedWhenAppropriate(array $config, int $expectedCalls): void
    {
        $app = $this->createMock(Application::class);
        $apiKeyRepo = $this->createMock(ApiKeyRepositoryInterface::class);
        $apiKeyRepo->expects($this->exactly($expectedCalls))->method('createInitialApiKey');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly($expectedCalls))->method('getRepository')->with(ApiKey::class)->willReturn(
            $apiKeyRepo,
        );
        $this->container->expects($this->exactly($expectedCalls + 1))->method('get')->willReturnMap([
            ['config', $config],
            [EntityManager::class, $em],
        ]);

        $result = ($this->delegator)($this->container, '', fn () => $app);

        self::assertSame($result, $app);
    }

    public static function provideConfigs(): iterable
    {
        yield 'no api key' => [[], 0];
        yield 'null api key' => [['initial_api_key' => null], 0];
        yield 'empty api key' => [['initial_api_key' => ''], 0];
        yield 'valid api key' => [['initial_api_key' => 'the_initial_key'], 1];
    }
}

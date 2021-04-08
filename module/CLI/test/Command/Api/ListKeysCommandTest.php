<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\ListKeysCommand;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class ListKeysCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new ListKeysCommand($this->apiKeyService->reveal()));
    }

    /**
     * @test
     * @dataProvider provideKeysAndOutputs
     */
    public function returnsExpectedOutput(array $keys, bool $enabledOnly, string $expected): void
    {
        $listKeys = $this->apiKeyService->listKeys($enabledOnly)->willReturn($keys);

        $this->commandTester->execute(['--enabled-only' => $enabledOnly]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals($expected, $output);
        $listKeys->shouldHaveBeenCalledOnce();
    }

    public function provideKeysAndOutputs(): iterable
    {
        yield 'all keys' => [
            [$apiKey1 = ApiKey::create(), $apiKey2 = ApiKey::create(), $apiKey3 = ApiKey::create()],
            false,
            <<<OUTPUT
            +--------------------------------------+------+------------+-----------------+-------+
            | Key                                  | Name | Is enabled | Expiration date | Roles |
            +--------------------------------------+------+------------+-----------------+-------+
            | {$apiKey1} | -    | +++        | -               | Admin |
            | {$apiKey2} | -    | +++        | -               | Admin |
            | {$apiKey3} | -    | +++        | -               | Admin |
            +--------------------------------------+------+------------+-----------------+-------+

            OUTPUT,
        ];
        yield 'enabled keys' => [
            [$apiKey1 = ApiKey::create()->disable(), $apiKey2 = ApiKey::create()],
            true,
            <<<OUTPUT
            +--------------------------------------+------+-----------------+-------+
            | Key                                  | Name | Expiration date | Roles |
            +--------------------------------------+------+-----------------+-------+
            | {$apiKey1} | -    | -               | Admin |
            | {$apiKey2} | -    | -               | Admin |
            +--------------------------------------+------+-----------------+-------+

            OUTPUT,
        ];
        yield 'with roles' => [
            [
                $apiKey1 = ApiKey::create(),
                $apiKey2 = $this->apiKeyWithRoles([RoleDefinition::forAuthoredShortUrls()]),
                $apiKey3 = $this->apiKeyWithRoles([RoleDefinition::forDomain((new Domain('example.com'))->setId('1'))]),
                $apiKey4 = ApiKey::create(),
                $apiKey5 = $this->apiKeyWithRoles([
                    RoleDefinition::forAuthoredShortUrls(),
                    RoleDefinition::forDomain((new Domain('example.com'))->setId('1')),
                ]),
                $apiKey6 = ApiKey::create(),
            ],
            true,
            <<<OUTPUT
            +--------------------------------------+------+-----------------+--------------------------+
            | Key                                  | Name | Expiration date | Roles                    |
            +--------------------------------------+------+-----------------+--------------------------+
            | {$apiKey1} | -    | -               | Admin                    |
            | {$apiKey2} | -    | -               | Author only              |
            | {$apiKey3} | -    | -               | Domain only: example.com |
            | {$apiKey4} | -    | -               | Admin                    |
            | {$apiKey5} | -    | -               | Author only              |
            |                                      |      |                 | Domain only: example.com |
            | {$apiKey6} | -    | -               | Admin                    |
            +--------------------------------------+------+-----------------+--------------------------+

            OUTPUT,
        ];
        yield 'with names' => [
            [
                $apiKey1 = ApiKey::fromMeta(ApiKeyMeta::withName('Alice')),
                $apiKey2 = ApiKey::fromMeta(ApiKeyMeta::withName('Alice and Bob')),
                $apiKey3 = ApiKey::fromMeta(ApiKeyMeta::withName('')),
                $apiKey4 = ApiKey::create(),
            ],
            true,
            <<<OUTPUT
            +--------------------------------------+---------------+-----------------+-------+
            | Key                                  | Name          | Expiration date | Roles |
            +--------------------------------------+---------------+-----------------+-------+
            | {$apiKey1} | Alice         | -               | Admin |
            | {$apiKey2} | Alice and Bob | -               | Admin |
            | {$apiKey3} |               | -               | Admin |
            | {$apiKey4} | -             | -               | Admin |
            +--------------------------------------+---------------+-----------------+-------+

            OUTPUT,
        ];
    }

    private function apiKeyWithRoles(array $roles): ApiKey
    {
        $apiKey = ApiKey::create();
        foreach ($roles as $role) {
            $apiKey->registerRole($role);
        }

        return $apiKey;
    }
}

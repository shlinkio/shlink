<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Api\ListKeysCommand;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
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
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new ListKeysCommand($this->apiKeyService));
    }

    /**
     * @test
     * @dataProvider provideKeysAndOutputs
     */
    public function returnsExpectedOutput(array $keys, bool $enabledOnly, string $expected): void
    {
        $this->apiKeyService->expects($this->once())->method('listKeys')->with($enabledOnly)->willReturn($keys);

        $this->commandTester->execute(['--enabled-only' => $enabledOnly]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals($expected, $output);
    }

    public static function provideKeysAndOutputs(): iterable
    {
        $dateInThePast = Chronos::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');

        yield 'all keys' => [
            [
                $apiKey1 = ApiKey::create()->disable(),
                $apiKey2 = ApiKey::fromMeta(ApiKeyMeta::withExpirationDate($dateInThePast)),
                $apiKey3 = ApiKey::create(),
            ],
            false,
            <<<OUTPUT
            +--------------------------------------+------+------------+---------------------------+-------+
            | Key                                  | Name | Is enabled | Expiration date           | Roles |
            +--------------------------------------+------+------------+---------------------------+-------+
            | {$apiKey1} | -    | ---        | -                         | Admin |
            +--------------------------------------+------+------------+---------------------------+-------+
            | {$apiKey2} | -    | ---        | 2020-01-01T00:00:00+00:00 | Admin |
            +--------------------------------------+------+------------+---------------------------+-------+
            | {$apiKey3} | -    | +++        | -                         | Admin |
            +--------------------------------------+------+------------+---------------------------+-------+

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
            +--------------------------------------+------+-----------------+-------+
            | {$apiKey2} | -    | -               | Admin |
            +--------------------------------------+------+-----------------+-------+

            OUTPUT,
        ];
        yield 'with roles' => [
            [
                $apiKey1 = ApiKey::create(),
                $apiKey2 = self::apiKeyWithRoles([RoleDefinition::forAuthoredShortUrls()]),
                $apiKey3 = self::apiKeyWithRoles(
                    [RoleDefinition::forDomain(self::domainWithId(Domain::withAuthority('example.com')))],
                ),
                $apiKey4 = ApiKey::create(),
                $apiKey5 = self::apiKeyWithRoles([
                    RoleDefinition::forAuthoredShortUrls(),
                    RoleDefinition::forDomain(self::domainWithId(Domain::withAuthority('example.com'))),
                ]),
                $apiKey6 = ApiKey::create(),
            ],
            true,
            <<<OUTPUT
            +--------------------------------------+------+-----------------+--------------------------+
            | Key                                  | Name | Expiration date | Roles                    |
            +--------------------------------------+------+-----------------+--------------------------+
            | {$apiKey1} | -    | -               | Admin                    |
            +--------------------------------------+------+-----------------+--------------------------+
            | {$apiKey2} | -    | -               | Author only              |
            +--------------------------------------+------+-----------------+--------------------------+
            | {$apiKey3} | -    | -               | Domain only: example.com |
            +--------------------------------------+------+-----------------+--------------------------+
            | {$apiKey4} | -    | -               | Admin                    |
            +--------------------------------------+------+-----------------+--------------------------+
            | {$apiKey5} | -    | -               | Author only              |
            |                                      |      |                 | Domain only: example.com |
            +--------------------------------------+------+-----------------+--------------------------+
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
            +--------------------------------------+---------------+-----------------+-------+
            | {$apiKey2} | Alice and Bob | -               | Admin |
            +--------------------------------------+---------------+-----------------+-------+
            | {$apiKey3} |               | -               | Admin |
            +--------------------------------------+---------------+-----------------+-------+
            | {$apiKey4} | -             | -               | Admin |
            +--------------------------------------+---------------+-----------------+-------+

            OUTPUT,
        ];
    }

    private static function apiKeyWithRoles(array $roles): ApiKey
    {
        $apiKey = ApiKey::create();
        foreach ($roles as $role) {
            $apiKey->registerRole($role);
        }

        return $apiKey;
    }

    private static function domainWithId(Domain $domain): Domain
    {
        $domain->setId('1');
        return $domain;
    }
}

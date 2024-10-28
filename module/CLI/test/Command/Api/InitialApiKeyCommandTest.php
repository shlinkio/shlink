<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Api\InitialApiKeyCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class InitialApiKeyCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new InitialApiKeyCommand($this->apiKeyService));
    }

    #[Test, DataProvider('provideParams')]
    public function initialKeyIsCreatedWithProvidedValue(
        ApiKey|null $result,
        bool $verbose,
        string $expectedOutput,
    ): void {
        $this->apiKeyService->expects($this->once())->method('createInitial')->with('the_key')->willReturn($result);

        $this->commandTester->execute(
            ['apiKey' => 'the_key'],
            ['verbosity' => $verbose ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_NORMAL],
        );
        $output = $this->commandTester->getDisplay();

        self::assertEquals($expectedOutput, $output);
    }

    public static function provideParams(): iterable
    {
        yield 'api key created, no verbose' => [ApiKey::create(), false, ''];
        yield 'api key created, verbose' => [ApiKey::create(), true, ''];
        yield 'no api key created, no verbose' => [null, false, ''];
        yield 'no api key created, verbose' => [null, true, <<<OUT
            Other API keys already exist. Initial API key creation skipped.

            OUT,
        ];
    }
}

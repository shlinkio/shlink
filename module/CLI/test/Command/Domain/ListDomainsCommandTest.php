<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Domain;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Domain\ListDomainsCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class ListDomainsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $domainService;

    public function setUp(): void
    {
        $this->domainService = $this->prophesize(DomainServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new ListDomainsCommand($this->domainService->reveal()));
    }

    /** @test */
    public function allDomainsAreProperlyPrinted(): void
    {
        $expectedOutput = <<<OUTPUT
        +---------+------------+
        | Domain  | Is default |
        +---------+------------+
        | foo.com | Yes        |
        | bar.com | No         |
        | baz.com | No         |
        +---------+------------+

        OUTPUT;
        $listDomains = $this->domainService->listDomains()->willReturn([
            new DomainItem('foo.com', true),
            new DomainItem('bar.com', false),
            new DomainItem('baz.com', false),
        ]);

        $this->commandTester->execute([]);

        self::assertEquals($expectedOutput, $this->commandTester->getDisplay());
        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        $listDomains->shouldHaveBeenCalledOnce();
    }
}

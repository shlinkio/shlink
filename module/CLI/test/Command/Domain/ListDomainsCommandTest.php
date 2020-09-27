<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Domain;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Domain\ListDomainsCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListDomainsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $domainService;

    public function setUp(): void
    {
        $this->domainService = $this->prophesize(DomainServiceInterface::class);

        $command = new ListDomainsCommand($this->domainService->reveal(), 'foo.com');
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
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
        $listDomains = $this->domainService->listDomainsWithout('foo.com')->willReturn([
            new Domain('bar.com'),
            new Domain('baz.com'),
        ]);

        $this->commandTester->execute([]);

        self::assertEquals($expectedOutput, $this->commandTester->getDisplay());
        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        $listDomains->shouldHaveBeenCalledOnce();
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\RenameTagCommand;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RenameTagCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);

        $command = new RenameTagCommand($this->tagService->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function errorIsPrintedIfExceptionIsThrown(): void
    {
        $oldName = 'foo';
        $newName = 'bar';
        $renameTag = $this->tagService->renameTag($oldName, $newName)->willThrow(TagNotFoundException::fromTag('foo'));

        $this->commandTester->execute([
            'oldName' => $oldName,
            'newName' => $newName,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Tag with name "foo" could not be found', $output);
        $renameTag->shouldHaveBeenCalled();
    }

    /** @test */
    public function successIsPrintedIfNoErrorOccurs(): void
    {
        $oldName = 'foo';
        $newName = 'bar';
        $renameTag = $this->tagService->renameTag($oldName, $newName)->willReturn(new Tag($newName));

        $this->commandTester->execute([
            'oldName' => $oldName,
            'newName' => $newName,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Tag properly renamed', $output);
        $renameTag->shouldHaveBeenCalled();
    }
}

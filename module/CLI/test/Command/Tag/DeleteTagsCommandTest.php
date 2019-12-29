<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\DeleteTagsCommand;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteTagsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);

        $command = new DeleteTagsCommand($this->tagService->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function errorIsReturnedWhenNoTagsAreProvided(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('You have to provide at least one tag name', $output);
    }

    /** @test */
    public function serviceIsInvokedOnSuccess(): void
    {
        $tagNames = ['foo', 'bar'];
        $deleteTags = $this->tagService->deleteTags($tagNames)->will(function () {
        });

        $this->commandTester->execute([
            '--name' => $tagNames,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Tags properly deleted', $output);
        $deleteTags->shouldHaveBeenCalled();
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\DeleteTagsCommand;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteTagsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new DeleteTagsCommand($this->tagService->reveal()));
    }

    /** @test */
    public function errorIsReturnedWhenNoTagsAreProvided(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('You have to provide at least one tag name', $output);
    }

    /** @test */
    public function serviceIsInvokedOnSuccess(): void
    {
        $tagNames = ['foo', 'bar'];
        $deleteTags = $this->tagService->deleteTags($tagNames)->will(function (): void {
        });

        $this->commandTester->execute([
            '--name' => $tagNames,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Tags properly deleted', $output);
        $deleteTags->shouldHaveBeenCalled();
    }
}

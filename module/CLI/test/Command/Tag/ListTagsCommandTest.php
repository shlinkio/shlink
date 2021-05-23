<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\ListTagsCommand;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class ListTagsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new ListTagsCommand($this->tagService->reveal()));
    }

    /** @test */
    public function noTagsPrintsEmptyMessage(): void
    {
        $tagsInfo = $this->tagService->tagsInfo()->willReturn([]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('No tags found', $output);
        $tagsInfo->shouldHaveBeenCalled();
    }

    /** @test */
    public function listOfTagsIsPrinted(): void
    {
        $tagsInfo = $this->tagService->tagsInfo()->willReturn([
            new TagInfo(new Tag('foo'), 10, 2),
            new TagInfo(new Tag('bar'), 7, 32),
        ]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('| foo', $output);
        self::assertStringContainsString('| bar', $output);
        self::assertStringContainsString('| 10 ', $output);
        self::assertStringContainsString('| 2 ', $output);
        self::assertStringContainsString('| 7 ', $output);
        self::assertStringContainsString('| 32 ', $output);
        $tagsInfo->shouldHaveBeenCalled();
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\ListTagsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
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
        $tagsInfo = $this->tagService->tagsInfo(Argument::any())->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('No tags found', $output);
        $tagsInfo->shouldHaveBeenCalled();
    }

    /** @test */
    public function listOfTagsIsPrinted(): void
    {
        $tagsInfo = $this->tagService->tagsInfo(Argument::any())->willReturn(new Paginator(new ArrayAdapter([
            new TagInfo('foo', 10, 2),
            new TagInfo('bar', 7, 32),
        ])));

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(
            <<<OUTPUT
            +------+-------------+---------------+
            | Name | URLs amount | Visits amount |
            +------+-------------+---------------+
            | foo  | 10          | 2             |
            | bar  | 7           | 32            |
            +------+-------------+---------------+
            
            OUTPUT,
            $output,
        );
        $tagsInfo->shouldHaveBeenCalled();
    }
}

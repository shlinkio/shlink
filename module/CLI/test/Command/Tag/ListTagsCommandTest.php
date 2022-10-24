<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
    private MockObject & TagServiceInterface $tagService;

    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new ListTagsCommand($this->tagService));
    }

    /** @test */
    public function noTagsPrintsEmptyMessage(): void
    {
        $this->tagService->expects($this->once())->method('tagsInfo')->withAnyParameters()->willReturn(
            new Paginator(new ArrayAdapter([])),
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('No tags found', $output);
    }

    /** @test */
    public function listOfTagsIsPrinted(): void
    {
        $this->tagService->expects($this->once())->method('tagsInfo')->withAnyParameters()->willReturn(
            new Paginator(new ArrayAdapter([
                new TagInfo('foo', 10, 2),
                new TagInfo('bar', 7, 32),
            ])),
        );

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
    }
}

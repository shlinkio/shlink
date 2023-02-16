<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Tag\DeleteTagsCommand;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteTagsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private MockObject & TagServiceInterface $tagService;

    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new DeleteTagsCommand($this->tagService));
    }

    #[Test]
    public function errorIsReturnedWhenNoTagsAreProvided(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('You have to provide at least one tag name', $output);
    }

    #[Test]
    public function serviceIsInvokedOnSuccess(): void
    {
        $tagNames = ['foo', 'bar'];
        $this->tagService->expects($this->once())->method('deleteTags')->with($tagNames);

        $this->commandTester->execute([
            '--name' => $tagNames,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Tags properly deleted', $output);
    }
}

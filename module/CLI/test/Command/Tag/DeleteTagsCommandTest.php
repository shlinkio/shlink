<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\DeleteTagsCommand;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class DeleteTagsCommandTest extends TestCase
{
    /**
     * @var DeleteTagsCommand
     */
    private $command;
    /**
     * @var CommandTester
     */
    private $commandTester;
    /**
     * @var ObjectProphecy
     */
    private $tagService;

    public function setUp()
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);

        $command = new DeleteTagsCommand($this->tagService->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function errorIsReturnedWhenNoTagsAreProvided()
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('You have to provide at least one tag name', $output);
    }

    /**
     * @test
     */
    public function serviceIsInvokedOnSuccess()
    {
        $tagNames = ['foo', 'bar'];
        /** @var MethodProphecy $deleteTags */
        $deleteTags = $this->tagService->deleteTags($tagNames)->will(function () {
        });

        $this->commandTester->execute([
            '--name' => $tagNames,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('Tags properly deleted', $output);
        $deleteTags->shouldHaveBeenCalled();
    }
}

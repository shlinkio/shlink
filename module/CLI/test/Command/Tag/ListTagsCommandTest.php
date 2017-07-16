<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\ListTagsCommand;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class ListTagsCommandTest extends TestCase
{
    /**
     * @var ListTagsCommand
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

        $command = new ListTagsCommand($this->tagService->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function noTagsPrintsEmptyMessage()
    {
        /** @var MethodProphecy $listTags */
        $listTags = $this->tagService->listTags()->willReturn([]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('No tags yet', $output);
        $listTags->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function listOfTagsIsPrinted()
    {
        /** @var MethodProphecy $listTags */
        $listTags = $this->tagService->listTags()->willReturn([
            (new Tag())->setName('foo'),
            (new Tag())->setName('bar'),
        ]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('foo', $output);
        $this->assertContains('bar', $output);
        $listTags->shouldHaveBeenCalled();
    }
}

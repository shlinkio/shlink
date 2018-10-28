<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\RenameTagCommand;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class RenameTagCommandTest extends TestCase
{
    /**
     * @var RenameTagCommand
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

        $command = new RenameTagCommand($this->tagService->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function errorIsPrintedIfExceptionIsThrown()
    {
        $oldName = 'foo';
        $newName = 'bar';
        /** @var MethodProphecy $renameTag */
        $renameTag = $this->tagService->renameTag($oldName, $newName)->willThrow(EntityDoesNotExistException::class);

        $this->commandTester->execute([
            'oldName' => $oldName,
            'newName' => $newName,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('A tag with name "foo" was not found', $output);
        $renameTag->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function successIsPrintedIfNoErrorOccurs()
    {
        $oldName = 'foo';
        $newName = 'bar';
        /** @var MethodProphecy $renameTag */
        $renameTag = $this->tagService->renameTag($oldName, $newName)->willReturn(new Tag($newName));

        $this->commandTester->execute([
            'oldName' => $oldName,
            'newName' => $newName,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('Tag properly renamed', $output);
        $renameTag->shouldHaveBeenCalled();
    }
}

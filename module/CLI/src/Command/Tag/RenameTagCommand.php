<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class RenameTagCommand extends Command
{
    const NAME = 'tag:rename';

    /**
     * @var TagServiceInterface
     */
    private $tagService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TagServiceInterface $tagService, TranslatorInterface $translator)
    {
        $this->tagService = $tagService;
        $this->translator = $translator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription($this->translator->translate('Renames one existing tag.'))
            ->addArgument('oldName', InputArgument::REQUIRED, $this->translator->translate('Current name of the tag.'))
            ->addArgument('newName', InputArgument::REQUIRED, $this->translator->translate('New name of the tag.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oldName = $input->getArgument('oldName');
        $newName = $input->getArgument('newName');

        try {
            $this->tagService->renameTag($oldName, $newName);
            $output->writeln(sprintf('<info>%s</info>', $this->translator->translate('Tag properly renamed.')));
        } catch (EntityDoesNotExistException $e) {
            $output->writeln('<error>' . sprintf($this->translator->translate(
                'A tag with name "%s" was not found'
            ), $oldName) . '</error>');
        }
    }
}

<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class RenameTagCommand extends Command
{
    public const NAME = 'tag:rename';

    /** @var TagServiceInterface */
    private $tagService;

    public function __construct(TagServiceInterface $tagService)
    {
        parent::__construct();
        $this->tagService = $tagService;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Renames one existing tag.')
            ->addArgument('oldName', InputArgument::REQUIRED, 'Current name of the tag.')
            ->addArgument('newName', InputArgument::REQUIRED, 'New name of the tag.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $oldName = $input->getArgument('oldName');
        $newName = $input->getArgument('newName');

        try {
            $this->tagService->renameTag($oldName, $newName);
            $io->success('Tag properly renamed.');
            return ExitCodes::EXIT_SUCCESS;
        } catch (EntityDoesNotExistException $e) {
            $io->error(sprintf('A tag with name "%s" was not found', $oldName));
            return ExitCodes::EXIT_FAILURE;
        }
    }
}

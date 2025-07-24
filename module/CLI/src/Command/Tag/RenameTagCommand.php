<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RenameTagCommand extends Command
{
    public const string NAME = 'tag:rename';

    public function __construct(private readonly TagServiceInterface $tagService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Renames one existing tag.')
            ->addArgument('oldName', InputArgument::REQUIRED, 'Current name of the tag.')
            ->addArgument('newName', InputArgument::REQUIRED, 'New name of the tag.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $oldName = $input->getArgument('oldName');
        $newName = $input->getArgument('newName');

        try {
            $this->tagService->renameTag(Renaming::fromNames($oldName, $newName));
            $io->success('Tag properly renamed.');
            return Command::SUCCESS;
        } catch (TagNotFoundException | TagConflictException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(RenameTagCommand::NAME, 'Renames one existing tag.')]
class RenameTagCommand extends Command
{
    public const string NAME = 'tag:rename';

    public function __construct(private readonly TagServiceInterface $tagService)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Current name of the tag.')] string $oldName,
        #[Argument('New name of the tag.')] string $newName,
    ): int {
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

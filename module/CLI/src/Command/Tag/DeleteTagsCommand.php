<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: DeleteTagsCommand::NAME, description: 'Deletes one or more tags.')]
class DeleteTagsCommand extends Command
{
    public const string NAME = 'tag:delete';

    public function __construct(private readonly TagServiceInterface $tagService)
    {
        parent::__construct();
    }

    /**
     * @param string[] $tagNames
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Option('The name of the tags to delete', name: 'name', shortcut: 't')] array $tagNames = [],
    ): int {
        if (empty($tagNames)) {
            $io->warning('You have to provide at least one tag name');
            return self::INVALID;
        }

        $this->tagService->deleteTags($tagNames);
        $io->success('Tags properly deleted');

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteTagsCommand extends Command
{
    public const NAME = 'tag:delete';

    public function __construct(private TagServiceInterface $tagService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deletes one or more tags.')
            ->addOption(
                'name',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The name of the tags to delete',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $tagNames = $input->getOption('name');

        if (empty($tagNames)) {
            $io->warning('You have to provide at least one tag name');
            return ExitCodes::EXIT_WARNING;
        }

        $this->tagService->deleteTags($tagNames);
        $io->success('Tags properly deleted');
        return ExitCodes::EXIT_SUCCESS;
    }
}

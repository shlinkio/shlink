<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Functional\map;

class ListTagsCommand extends Command
{
    public const NAME = 'tag:list';

    public function __construct(private TagServiceInterface $tagService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Lists existing tags.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        ShlinkTable::default($output)->render(['Name', 'URLs amount', 'Visits amount'], $this->getTagsRows());
        return ExitCodes::EXIT_SUCCESS;
    }

    private function getTagsRows(): array
    {
        $tags = $this->tagService->tagsInfo(TagsParams::fromRawData([]))->getCurrentPageResults();
        if (empty($tags)) {
            return [['No tags found', '-', '-']];
        }

        return map(
            $tags,
            static fn (TagInfo $tagInfo) => [$tagInfo->tag(), $tagInfo->shortUrlsCount(), $tagInfo->visitsCount()],
        );
    }
}

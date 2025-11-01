<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_map;

#[AsCommand(ListTagsCommand::NAME, 'Lists existing tags.')]
class ListTagsCommand extends Command
{
    public const string NAME = 'tag:list';

    public function __construct(private readonly TagServiceInterface $tagService)
    {
        parent::__construct();
    }

    public function __invoke(SymfonyStyle $io): int
    {
        ShlinkTable::default($io)->render(['Name', 'URLs amount', 'Visits amount'], $this->getTagsRows());
        return self::SUCCESS;
    }

    private function getTagsRows(): array
    {
        $tags = $this->tagService->tagsInfo(TagsParams::fromRawData([]))->getCurrentPageResults();
        if (empty($tags)) {
            return [['No tags found', '-', '-']];
        }

        return array_map(
            static fn (TagInfo $tagInfo) => [$tagInfo->tag, $tagInfo->shortUrlsCount, $tagInfo->visitsSummary->total],
            [...$tags],
        );
    }
}

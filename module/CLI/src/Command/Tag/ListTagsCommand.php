<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Common\Console\ShlinkTable;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Functional\map;

class ListTagsCommand extends Command
{
    public const NAME = 'tag:list';

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
            ->setDescription('Lists existing tags.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        ShlinkTable::fromOutput($output)->render(['Name'], $this->getTagsRows());
        return 0;
    }

    private function getTagsRows(): array
    {
        $tags = $this->tagService->listTags();
        if (empty($tags)) {
            return [['No tags yet']];
        }

        return map($tags, function (Tag $tag) {
            return [(string) $tag];
        });
    }
}

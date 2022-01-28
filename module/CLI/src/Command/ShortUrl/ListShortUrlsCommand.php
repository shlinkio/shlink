<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Util\AbstractWithDateRangeCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtilsTrait;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlsParamsInputFilter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_keys;
use function array_pad;
use function explode;
use function Functional\map;
use function implode;
use function sprintf;

class ListShortUrlsCommand extends AbstractWithDateRangeCommand
{
    use PagerfantaUtilsTrait;

    public const NAME = 'short-url:list';

    public function __construct(
        private ShortUrlServiceInterface $shortUrlService,
        private DataTransformerInterface $transformer,
    ) {
        parent::__construct();
    }

    protected function doConfigure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all short URLs')
            ->addOption(
                'page',
                'p',
                InputOption::VALUE_REQUIRED,
                'The first page to list (10 items per page unless "--all" is provided).',
                '1',
            )
            ->addOption(
                'search-term',
                'st',
                InputOption::VALUE_REQUIRED,
                'A query used to filter results by searching for it on the longUrl and shortCode fields.',
            )
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_REQUIRED,
                'A comma-separated list of tags to filter results.',
            )
            ->addOption(
                'including-all-tags',
                'i',
                InputOption::VALUE_NONE,
                'If tags is provided, returns only short URLs having ALL tags.',
            )
            ->addOption(
                'order-by',
                'o',
                InputOption::VALUE_REQUIRED,
                'The field from which you want to order by. '
                    . 'Define ordering dir by passing ASC or DESC after "-" or ",".',
            )
            ->addOption(
                'show-tags',
                null,
                InputOption::VALUE_NONE,
                'Whether to display the tags or not.',
            )
            ->addOption(
                'show-api-key',
                'k',
                InputOption::VALUE_NONE,
                'Whether to display the API key from which the URL was generated or not.',
            )
            ->addOption(
                'show-api-key-name',
                'm',
                InputOption::VALUE_NONE,
                'Whether to display the API key name from which the URL was generated or not.',
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Disables pagination and just displays all existing URLs. Caution! If the amount of short URLs is big,'
                . ' this may end up failing due to memory usage.',
            );
    }

    protected function getStartDateDesc(string $optionName): string
    {
        return sprintf('Allows to filter short URLs, returning only those created after "%s".', $optionName);
    }

    protected function getEndDateDesc(string $optionName): string
    {
        return sprintf('Allows to filter short URLs, returning only those created before "%s".', $optionName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $page = (int) $input->getOption('page');
        $searchTerm = $input->getOption('search-term');
        $tags = $input->getOption('tags');
        $tagsMode = $input->getOption('including-all-tags') === true
            ? ShortUrlsParams::TAGS_MODE_ALL
            : ShortUrlsParams::TAGS_MODE_ANY;
        $tags = ! empty($tags) ? explode(',', $tags) : [];
        $all = $input->getOption('all');
        $startDate = $this->getStartDateOption($input, $output);
        $endDate = $this->getEndDateOption($input, $output);
        $orderBy = $this->processOrderBy($input);
        $columnsMap = $this->resolveColumnsMap($input);

        $data = [
            ShortUrlsParamsInputFilter::SEARCH_TERM => $searchTerm,
            ShortUrlsParamsInputFilter::TAGS => $tags,
            ShortUrlsParamsInputFilter::TAGS_MODE => $tagsMode,
            ShortUrlsParamsInputFilter::ORDER_BY => $orderBy,
            ShortUrlsParamsInputFilter::START_DATE => $startDate?->toAtomString(),
            ShortUrlsParamsInputFilter::END_DATE => $endDate?->toAtomString(),
        ];

        if ($all) {
            $data[ShortUrlsParamsInputFilter::ITEMS_PER_PAGE] = Paginator::ALL_ITEMS;
        }

        do {
            $data[ShortUrlsParamsInputFilter::PAGE] = $page;
            $result = $this->renderPage($output, $columnsMap, ShortUrlsParams::fromRawData($data), $all);
            $page++;

            $continue = $result->hasNextPage() && $io->confirm(
                sprintf('Continue with page <options=bold>%s</>?', $page),
                false,
            );
        } while ($continue);

        $io->newLine();
        $io->success('Short URLs properly listed');

        return ExitCodes::EXIT_SUCCESS;
    }

    private function renderPage(
        OutputInterface $output,
        array $columnsMap,
        ShortUrlsParams $params,
        bool $all,
    ): Paginator {
        $shortUrls = $this->shortUrlService->listShortUrls($params);

        $rows = map($shortUrls, function (ShortUrl $shortUrl) use ($columnsMap) {
            $rawShortUrl = $this->transformer->transform($shortUrl);
            return map($columnsMap, fn (callable $call) => $call($rawShortUrl, $shortUrl));
        });

        ShlinkTable::default($output)->render(
            array_keys($columnsMap),
            $rows,
            $all ? null : $this->formatCurrentPageMessage($shortUrls, 'Page %s of %s'),
        );

        return $shortUrls;
    }

    private function processOrderBy(InputInterface $input): ?string
    {
        $orderBy = $input->getOption('order-by');
        if (empty($orderBy)) {
            return null;
        }

        [$field, $dir] = array_pad(explode(',', $orderBy), 2, null);
        return $dir === null ? $field : sprintf('%s-%s', $field, $dir);
    }

    private function resolveColumnsMap(InputInterface $input): array
    {
        $pickProp = static fn (string $prop): callable => static fn (array $shortUrl) => $shortUrl[$prop];
        $columnsMap = [
            'Short Code' => $pickProp('shortCode'),
            'Title' => $pickProp('title'),
            'Short URL' => $pickProp('shortUrl'),
            'Long URL' => $pickProp('longUrl'),
            'Date created' => $pickProp('dateCreated'),
            'Visits count' => $pickProp('visitsCount'),
        ];
        if ($input->getOption('show-tags')) {
            $columnsMap['Tags'] = static fn (array $shortUrl): string => implode(', ', $shortUrl['tags']);
        }
        if ($input->getOption('show-api-key')) {
            $columnsMap['API Key'] = static fn (array $_, ShortUrl $shortUrl): string =>
                (string) $shortUrl->authorApiKey();
        }
        if ($input->getOption('show-api-key-name')) {
            $columnsMap['API Key Name'] = static fn (array $_, ShortUrl $shortUrl): ?string =>
                $shortUrl->authorApiKey()?->name();
        }

        return $columnsMap;
    }
}

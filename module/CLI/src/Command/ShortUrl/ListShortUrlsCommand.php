<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Util\AbstractWithDateRangeCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtilsTrait;
use Shlinkio\Shlink\Core\Model\ShortUrlsOrdering;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Core\Validation\ShortUrlsParamsInputFilter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_flip;
use function array_intersect_key;
use function array_values;
use function count;
use function explode;
use function implode;
use function sprintf;

class ListShortUrlsCommand extends AbstractWithDateRangeCommand
{
    use PagerfantaUtilsTrait;

    public const NAME = 'short-url:list';
    private const COLUMNS_WHITELIST = [
        'shortCode',
        'shortUrl',
        'longUrl',
        'dateCreated',
        'visitsCount',
        'tags',
    ];

    private ShortUrlServiceInterface $shortUrlService;
    private ShortUrlDataTransformer $transformer;

    public function __construct(ShortUrlServiceInterface $shortUrlService, array $domainConfig)
    {
        parent::__construct();
        $this->shortUrlService = $shortUrlService;
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
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
            ->addOptionWithDeprecatedFallback(
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
            ->addOptionWithDeprecatedFallback(
                'order-by',
                'o',
                InputOption::VALUE_REQUIRED,
                'The field from which we want to order by. Pass ASC or DESC separated by a comma.',
            )
            ->addOptionWithDeprecatedFallback(
                'show-tags',
                null,
                InputOption::VALUE_NONE,
                'Whether to display the tags or not.',
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
        $searchTerm = $this->getOptionWithDeprecatedFallback($input, 'search-term');
        $tags = $input->getOption('tags');
        $tags = ! empty($tags) ? explode(',', $tags) : [];
        $showTags = $this->getOptionWithDeprecatedFallback($input, 'show-tags');
        $all = $input->getOption('all');
        $startDate = $this->getStartDateOption($input, $output);
        $endDate = $this->getEndDateOption($input, $output);
        $orderBy = $this->processOrderBy($input);

        $data = [
            ShortUrlsParamsInputFilter::SEARCH_TERM => $searchTerm,
            ShortUrlsParamsInputFilter::TAGS => $tags,
            ShortUrlsOrdering::ORDER_BY => $orderBy,
            ShortUrlsParamsInputFilter::START_DATE => $startDate !== null ? $startDate->toAtomString() : null,
            ShortUrlsParamsInputFilter::END_DATE => $endDate !== null ? $endDate->toAtomString() : null,
        ];

        if ($all) {
            $data[ShortUrlsParamsInputFilter::ITEMS_PER_PAGE] = -1;
        }

        do {
            $data[ShortUrlsParamsInputFilter::PAGE] = $page;
            $result = $this->renderPage($output, $showTags, ShortUrlsParams::fromRawData($data), $all);
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

    private function renderPage(OutputInterface $output, bool $showTags, ShortUrlsParams $params, bool $all): Paginator
    {
        $result = $this->shortUrlService->listShortUrls($params);

        $headers = ['Short code', 'Short URL', 'Long URL', 'Date created', 'Visits count'];
        if ($showTags) {
            $headers[] = 'Tags';
        }

        $rows = [];
        foreach ($result as $row) {
            $shortUrl = $this->transformer->transform($row);
            if ($showTags) {
                $shortUrl['tags'] = implode(', ', $shortUrl['tags']);
            } else {
                unset($shortUrl['tags']);
            }

            $rows[] = array_values(array_intersect_key($shortUrl, array_flip(self::COLUMNS_WHITELIST)));
        }

        ShlinkTable::fromOutput($output)->render($headers, $rows, $all ? null : $this->formatCurrentPageMessage(
            $result,
            'Page %s of %s',
        ));

        return $result;
    }

    /**
     * @return array|string|null
     */
    private function processOrderBy(InputInterface $input)
    {
        $orderBy = $this->getOptionWithDeprecatedFallback($input, 'order-by');
        if (empty($orderBy)) {
            return null;
        }

        $orderBy = explode(',', $orderBy);
        return count($orderBy) === 1 ? $orderBy[0] : [$orderBy[0] => $orderBy[1]];
    }
}

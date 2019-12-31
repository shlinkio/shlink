<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\CLI\Command\Util\AbstractWithDateRangeCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\Paginator\Paginator;

use function array_flip;
use function array_intersect_key;
use function array_values;
use function count;
use function explode;
use function implode;
use function sprintf;

class ListShortUrlsCommand extends AbstractWithDateRangeCommand
{
    use PaginatorUtilsTrait;

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
                sprintf('The first page to list (%s items per page)', ShortUrlRepositoryAdapter::ITEMS_PER_PAGE),
                '1'
            )
            ->addOption(
                'searchTerm',
                'st',
                InputOption::VALUE_REQUIRED,
                'A query used to filter results by searching for it on the longUrl and shortCode fields'
            )
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_REQUIRED,
                'A comma-separated list of tags to filter results'
            )
            ->addOption(
                'orderBy',
                'o',
                InputOption::VALUE_REQUIRED,
                'The field from which we want to order by. Pass ASC or DESC separated by a comma'
            )
            ->addOption('showTags', null, InputOption::VALUE_NONE, 'Whether to display the tags or not');
    }

    protected function getStartDateDesc(): string
    {
        return 'Allows to filter short URLs, returning only those created after "startDate"';
    }

    protected function getEndDateDesc(): string
    {
        return 'Allows to filter short URLs, returning only those created before "endDate"';
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $page = (int) $input->getOption('page');
        $searchTerm = $input->getOption('searchTerm');
        $tags = $input->getOption('tags');
        $tags = ! empty($tags) ? explode(',', $tags) : [];
        $showTags = (bool) $input->getOption('showTags');
        $startDate = $this->getDateOption($input, $output, 'startDate');
        $endDate = $this->getDateOption($input, $output, 'endDate');
        $orderBy = $this->processOrderBy($input);

        do {
            $result = $this->renderPage($output, $page, $searchTerm, $tags, $showTags, $startDate, $endDate, $orderBy);
            $page++;

            $continue = $this->isLastPage($result)
                ? false
                : $io->confirm(sprintf('Continue with page <options=bold>%s</>?', $page), false);
        } while ($continue);

        $io->newLine();
        $io->success('Short URLs properly listed');

        return ExitCodes::EXIT_SUCCESS;
    }

    private function renderPage(
        OutputInterface $output,
        int $page,
        ?string $searchTerm,
        array $tags,
        bool $showTags,
        ?Chronos $startDate,
        ?Chronos $endDate,
        $orderBy
    ): Paginator {
        $result = $this->shortUrlService->listShortUrls(
            $page,
            $searchTerm,
            $tags,
            $orderBy,
            new DateRange($startDate, $endDate)
        );

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

        ShlinkTable::fromOutput($output)->render($headers, $rows, $this->formatCurrentPageMessage(
            $result,
            'Page %s of %s'
        ));

        return $result;
    }

    /**
     * @return array|string|null
     */
    private function processOrderBy(InputInterface $input)
    {
        $orderBy = $input->getOption('orderBy');
        if (empty($orderBy)) {
            return null;
        }

        $orderBy = explode(',', $orderBy);
        return count($orderBy) === 1 ? $orderBy[0] : [$orderBy[0] => $orderBy[1]];
    }
}

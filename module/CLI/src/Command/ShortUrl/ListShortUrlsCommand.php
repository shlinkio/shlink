<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\Common\Paginator\Adapter\PaginableRepositoryAdapter;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;
use function array_values;
use function count;
use function explode;
use function implode;
use function sprintf;

class ListShortUrlsCommand extends Command
{
    use PaginatorUtilsTrait;

    public const NAME = 'short-url:list';
    private const ALIASES = ['shortcode:list', 'short-code:list'];

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var array
     */
    private $domainConfig;

    public function __construct(
        ShortUrlServiceInterface $shortUrlService,
        TranslatorInterface $translator,
        array $domainConfig
    ) {
        $this->shortUrlService = $shortUrlService;
        $this->translator = $translator;
        parent::__construct();
        $this->domainConfig = $domainConfig;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription($this->translator->translate('List all short URLs'))
            ->addOption(
                'page',
                'p',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    $this->translator->translate('The first page to list (%s items per page)'),
                    PaginableRepositoryAdapter::ITEMS_PER_PAGE
                ),
                '1'
            )
            ->addOption(
                'searchTerm',
                's',
                InputOption::VALUE_OPTIONAL,
                $this->translator->translate(
                    'A query used to filter results by searching for it on the longUrl and shortCode fields'
                )
            )
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_OPTIONAL,
                $this->translator->translate('A comma-separated list of tags to filter results')
            )
            ->addOption(
                'orderBy',
                'o',
                InputOption::VALUE_OPTIONAL,
                $this->translator->translate(
                    'The field from which we want to order by. Pass ASC or DESC separated by a comma'
                )
            )
            ->addOption(
                'showTags',
                null,
                InputOption::VALUE_NONE,
                $this->translator->translate('Whether to display the tags or not')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $page = (int) $input->getOption('page');
        $searchTerm = $input->getOption('searchTerm');
        $tags = $input->getOption('tags');
        $tags = ! empty($tags) ? explode(',', $tags) : [];
        $showTags = $input->getOption('showTags');
        $transformer = new ShortUrlDataTransformer($this->domainConfig);

        do {
            $result = $this->shortUrlService->listShortUrls($page, $searchTerm, $tags, $this->processOrderBy($input));
            $page++;

            $headers = [
                $this->translator->translate('Short code'),
                $this->translator->translate('Short URL'),
                $this->translator->translate('Long URL'),
                $this->translator->translate('Date created'),
                $this->translator->translate('Visits count'),
            ];
            if ($showTags) {
                $headers[] = $this->translator->translate('Tags');
            }

            $rows = [];
            foreach ($result as $row) {
                $shortUrl = $transformer->transform($row);
                if ($showTags) {
                    $shortUrl['tags'] = implode(', ', $shortUrl['tags']);
                } else {
                    unset($shortUrl['tags']);
                }

                unset($shortUrl['originalUrl']);
                $rows[] = array_values($shortUrl);
            }
            $io->table($headers, $rows);

            if ($this->isLastPage($result)) {
                $continue = false;
                $io->success($this->translator->translate('Short URLs properly listed'));
            } else {
                $continue = $io->confirm(
                    sprintf($this->translator->translate('Continue with page') . ' <options=bold>%s</>?', $page),
                    false
                );
            }
        } while ($continue);
    }

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

<?php
namespace Shlinkio\Shlink\CLI\Command;

use Acelaya\UrlShortener\Service\ShortUrlService;
use Acelaya\UrlShortener\Service\ShortUrlServiceInterface;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Common\Paginator\Adapter\PaginableRepositoryAdapter;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ListShortcodesCommand extends Command
{
    use PaginatorUtilsTrait;

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;

    /**
     * ListShortcodesCommand constructor.
     * @param ShortUrlServiceInterface|ShortUrlService $shortUrlService
     *
     * @Inject({ShortUrlService::class})
     */
    public function __construct(ShortUrlServiceInterface $shortUrlService)
    {
        parent::__construct(null);
        $this->shortUrlService = $shortUrlService;
    }

    public function configure()
    {
        $this->setName('shortcode:list')
             ->setDescription('List all short URLs')
             ->addOption(
                 'page',
                 'p',
                 InputOption::VALUE_OPTIONAL,
                 sprintf('The first page to list (%s items per page)', PaginableRepositoryAdapter::ITEMS_PER_PAGE),
                 1
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $page = intval($input->getOption('page'));
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        do {
            $result = $this->shortUrlService->listShortUrls($page);
            $page++;
            $table = new Table($output);
            $table->setHeaders([
                'Short code',
                'Original URL',
                'Date created',
                'Visits count',
            ]);

            foreach ($result as $row) {
                $table->addRow(array_values($row->jsonSerialize()));
            }
            $table->render();

            if ($this->isLastPage($result)) {
                $continue = false;
                $output->writeln('<info>You have reached last page</info>');
            } else {
                $continue = $helper->ask($input, $output, new ConfirmationQuestion(
                    sprintf('<question>Continue with page <bg=cyan;options=bold>%s</>? (y/N)</question> ', $page),
                    false
                ));
            }
        } while ($continue);
    }
}

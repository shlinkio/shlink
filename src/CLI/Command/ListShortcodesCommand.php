<?php
namespace Acelaya\UrlShortener\CLI\Command;

use Acelaya\UrlShortener\Paginator\Adapter\PaginableRepositoryAdapter;
use Acelaya\UrlShortener\Service\ShortUrlService;
use Acelaya\UrlShortener\Service\ShortUrlServiceInterface;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ListShortcodesCommand extends Command
{
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
             ->addArgument(
                 'page',
                 InputArgument::OPTIONAL,
                 sprintf('The first page to list (%s items per page)', PaginableRepositoryAdapter::ITEMS_PER_PAGE),
                 1
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $page = intval($input->getArgument('page'));
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

            $question = new ConfirmationQuestion('<question>Continue with next page? (y/N)</question> ', false);
        } while ($helper->ask($input, $output, $question));
    }
}

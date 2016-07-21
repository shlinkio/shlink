<?php
namespace Shlinkio\Shlink\CLI\Command;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GetVisitsCommand extends Command
{
    /**
     * @var VisitsTrackerInterface
     */
    private $visitsTracker;

    /**
     * GetVisitsCommand constructor.
     * @param VisitsTrackerInterface|VisitsTracker $visitsTracker
     *
     * @Inject({VisitsTracker::class})
     */
    public function __construct(VisitsTrackerInterface $visitsTracker)
    {
        parent::__construct(null);
        $this->visitsTracker = $visitsTracker;
    }

    public function configure()
    {
        $this->setName('shortcode:visits')
            ->setDescription('Returns the detailed visits information for provided short code')
            ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code which visits we want to get');
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question(
            '<question>A short code was not provided. Which short code do you want to use?:</question> '
        );

        $shortCode = $helper->ask($input, $output, $question);
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $shortCode = $input->getArgument('shortCode');
        $visits = $this->visitsTracker->info($shortCode);
        $table = new Table($output);
        $table->setHeaders([
            'Referer',
            'Date',
            'Temote Address',
            'User agent',
        ]);

        foreach ($visits as $row) {
            $rowData = $row->jsonSerialize();
            // Unset location info
            unset($rowData['visitLocation']);

            $table->addRow(array_values($rowData));
        }
        $table->render();
    }
}

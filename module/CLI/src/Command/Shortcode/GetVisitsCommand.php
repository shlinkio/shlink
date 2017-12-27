<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Shortcode;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zend\I18n\Translator\TranslatorInterface;

class GetVisitsCommand extends Command
{
    const NAME = 'shortcode:visits';

    /**
     * @var VisitsTrackerInterface
     */
    private $visitsTracker;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(VisitsTrackerInterface $visitsTracker, TranslatorInterface $translator)
    {
        $this->visitsTracker = $visitsTracker;
        $this->translator = $translator;
        parent::__construct(null);
    }

    public function configure()
    {
        $this->setName(self::NAME)
            ->setDescription(
                $this->translator->translate('Returns the detailed visits information for provided short code')
            )
            ->addArgument(
                'shortCode',
                InputArgument::REQUIRED,
                $this->translator->translate('The short code which visits we want to get')
            )
            ->addOption(
                'startDate',
                's',
                InputOption::VALUE_OPTIONAL,
                $this->translator->translate('Allows to filter visits, returning only those older than start date')
            )
            ->addOption(
                'endDate',
                'e',
                InputOption::VALUE_OPTIONAL,
                $this->translator->translate('Allows to filter visits, returning only those newer than end date')
            );
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question(sprintf(
            '<question>%s</question> ',
            $this->translator->translate('A short code was not provided. Which short code do you want to use?:')
        ));

        $shortCode = $helper->ask($input, $output, $question);
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $shortCode = $input->getArgument('shortCode');
        $startDate = $this->getDateOption($input, 'startDate');
        $endDate = $this->getDateOption($input, 'endDate');

        $visits = $this->visitsTracker->info($shortCode, new DateRange($startDate, $endDate));
        $table = new Table($output);
        $table->setHeaders([
            $this->translator->translate('Referer'),
            $this->translator->translate('Date'),
            $this->translator->translate('Remote Address'),
            $this->translator->translate('User agent'),
        ]);

        foreach ($visits as $row) {
            $rowData = $row->jsonSerialize();
            // Unset location info
            unset($rowData['visitLocation']);

            $table->addRow(array_values($rowData));
        }
        $table->render();
    }

    protected function getDateOption(InputInterface $input, $key)
    {
        $value = $input->getOption($key);
        if (isset($value)) {
            $value = new \DateTime($value);
        }

        return $value;
    }
}

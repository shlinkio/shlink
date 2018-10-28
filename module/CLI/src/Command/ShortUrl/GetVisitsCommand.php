<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;
use function array_map;
use function Shlinkio\Shlink\Common\pick;

class GetVisitsCommand extends Command
{
    public const NAME = 'short-url:visits';
    private const ALIASES = ['shortcode:visits', 'short-code:visits'];

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
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
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

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $shortCode = $io->ask(
            $this->translator->translate('A short code was not provided. Which short code do you want to use?')
        );
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $shortCode = $input->getArgument('shortCode');
        $startDate = $this->getDateOption($input, 'startDate');
        $endDate = $this->getDateOption($input, 'endDate');

        $visits = $this->visitsTracker->info($shortCode, new DateRange($startDate, $endDate));
        $rows = array_map(function (Visit $visit) {
            $rowData = $visit->jsonSerialize();
            $rowData['country'] = $visit->getVisitLocation()->getCountryName();
            return pick($rowData, ['referer', 'date', 'userAgent', 'country']);
        }, $visits);
        $io->table([
            $this->translator->translate('Referer'),
            $this->translator->translate('Date'),
            $this->translator->translate('User agent'),
            $this->translator->translate('Country'),
        ], $rows);
    }

    private function getDateOption(InputInterface $input, $key)
    {
        $value = $input->getOption($key);
        return ! empty($value) ? Chronos::parse($value) : $value;
    }
}

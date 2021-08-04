<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Util\AbstractWithDateRangeCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\UnknownVisitLocation;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Functional\map;
use function Functional\select_keys;
use function Shlinkio\Shlink\Common\buildDateRange;
use function sprintf;

class GetVisitsCommand extends AbstractWithDateRangeCommand
{
    public const NAME = 'short-url:visits';

    public function __construct(private VisitsStatsHelperInterface $visitsHelper)
    {
        parent::__construct();
    }

    protected function doConfigure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the detailed visits information for provided short code')
            ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code which visits we want to get.')
            ->addOption('domain', 'd', InputOption::VALUE_REQUIRED, 'The domain for the short code.');
    }

    protected function getStartDateDesc(string $optionName): string
    {
        return sprintf('Allows to filter visits, returning only those older than "%s".', $optionName);
    }

    protected function getEndDateDesc(string $optionName): string
    {
        return sprintf('Allows to filter visits, returning only those newer than "%s".', $optionName);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $shortCode = $io->ask('A short code was not provided. Which short code do you want to use?');
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $identifier = ShortUrlIdentifier::fromCli($input);
        $startDate = $this->getStartDateOption($input, $output);
        $endDate = $this->getEndDateOption($input, $output);

        $paginator = $this->visitsHelper->visitsForShortUrl(
            $identifier,
            new VisitsParams(buildDateRange($startDate, $endDate)),
        );

        $rows = map($paginator->getCurrentPageResults(), function (Visit $visit) {
            $rowData = $visit->jsonSerialize();
            $rowData['country'] = ($visit->getVisitLocation() ?? new UnknownVisitLocation())->getCountryName();
            return select_keys($rowData, ['referer', 'date', 'userAgent', 'country']);
        });
        ShlinkTable::default($output)->render(['Referer', 'Date', 'User agent', 'Country'], $rows);

        return ExitCodes::EXIT_SUCCESS;
    }
}

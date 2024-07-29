<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Visit\AbstractVisitsListCommand;
use Shlinkio\Shlink\CLI\Input\ShortUrlIdentifierInput;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetShortUrlVisitsCommand extends AbstractVisitsListCommand
{
    public const NAME = 'short-url:visits';

    private ShortUrlIdentifierInput $shortUrlIdentifierInput;

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the detailed visits information for provided short code');
        $this->shortUrlIdentifierInput = new ShortUrlIdentifierInput(
            $this,
            shortCodeDesc: 'The short code which visits we want to get.',
            domainDesc: 'The domain for the short code.',
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $shortCode = $this->shortUrlIdentifierInput->shortCode($input);
        if (! empty($shortCode)) {
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $shortCode = $io->ask('A short code was not provided. Which short code do you want to use?');
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    /**
     * @return Paginator<Visit>
     */
    protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator
    {
        $identifier = $this->shortUrlIdentifierInput->toShortUrlIdentifier($input);
        return $this->visitsHelper->visitsForShortUrl($identifier, new VisitsParams($dateRange));
    }

    /**
     * @return array<string, string>
     */
    protected function mapExtraFields(Visit $visit): array
    {
        return [];
    }
}

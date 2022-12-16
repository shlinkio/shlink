<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Domain;

use Shlinkio\Shlink\CLI\Command\Visit\AbstractVisitsListCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class GetDomainVisitsCommand extends AbstractVisitsListCommand
{
    public const NAME = 'domain:visits';

    public function __construct(
        VisitsStatsHelperInterface $visitsHelper,
        private readonly ShortUrlStringifierInterface $shortUrlStringifier,
    ) {
        parent::__construct($visitsHelper);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the list of visits for provided domain.')
            ->addArgument('domain', InputArgument::REQUIRED, 'The domain which visits we want to get.');
    }

    protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator
    {
        $domain = $input->getArgument('domain');
        return $this->visitsHelper->visitsForDomain($domain, new VisitsParams($dateRange));
    }

    /**
     * @return array<string, string>
     */
    protected function mapExtraFields(Visit $visit): array
    {
        $shortUrl = $visit->getShortUrl();
        return $shortUrl === null ? [] : ['shortUrl' => $this->shortUrlStringifier->stringify($shortUrl)];
    }
}

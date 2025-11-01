<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\CLI\Command\Visit\AbstractVisitsListCommand;
use Shlinkio\Shlink\CLI\Input\DomainOption;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

use function sprintf;

class GetTagVisitsCommand extends AbstractVisitsListCommand
{
    public const string NAME = 'tag:visits';

    private readonly DomainOption $domainOption;

    public function __construct(
        VisitsStatsHelperInterface $visitsHelper,
        private readonly ShortUrlStringifierInterface $shortUrlStringifier,
    ) {
        parent::__construct($visitsHelper);
        $this->domainOption = new DomainOption($this, sprintf(
            'Return visits that belong to this domain only. Use %s keyword for visits in default domain',
            Domain::DEFAULT_AUTHORITY,
        ));
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the list of visits for provided tag.')
            ->addArgument('tag', InputArgument::REQUIRED, 'The tag which visits we want to get.');
    }

    /**
     * @return Paginator<Visit>
     */
    protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator
    {
        $tag = $input->getArgument('tag');
        return $this->visitsHelper->visitsForTag($tag, new WithDomainVisitsParams(
            dateRange: $dateRange,
            domain: $this->domainOption->get($input),
        ));
    }

    /**
     * @return array<string, string>
     */
    protected function mapExtraFields(Visit $visit): array
    {
        $shortUrl = $visit->shortUrl;
        return $shortUrl === null ? [] : ['shortUrl' => $this->shortUrlStringifier->stringify($shortUrl)];
    }
}

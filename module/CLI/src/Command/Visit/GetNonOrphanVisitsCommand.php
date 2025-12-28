<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Input\VisitsListInput;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(GetNonOrphanVisitsCommand::NAME, 'Returns the list of non-orphan visits')]
class GetNonOrphanVisitsCommand extends Command
{
    public const string NAME = 'visit:non-orphan';

    public function __construct(
        private readonly VisitsStatsHelperInterface $visitsHelper,
        private readonly ShortUrlStringifierInterface $shortUrlStringifier,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput] VisitsListInput $input,
        #[Option(
            'Return visits that belong to this domain only. Use ' . Domain::DEFAULT_AUTHORITY . ' keyword for visits '
            . 'in default domain',
            shortcut: 'd',
        )]
        string|null $domain = null,
    ): int {
        $paginator = $this->visitsHelper->nonOrphanVisits(new WithDomainVisitsParams(
            dateRange: $input->dateRange(),
            domain: $domain,
        ));
        [$rows, $headers] = VisitsCommandUtils::resolveRowsAndHeaders($paginator, $this->mapExtraFields(...));

        ShlinkTable::default($io)->render($headers, $rows);

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function mapExtraFields(Visit $visit): array
    {
        $shortUrl = $visit->shortUrl;
        return $shortUrl === null ? [] : ['shortUrl' => $this->shortUrlStringifier->stringify($shortUrl)];
    }
}

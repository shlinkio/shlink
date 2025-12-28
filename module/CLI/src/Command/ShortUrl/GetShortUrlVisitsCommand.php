<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Visit\VisitsCommandUtils;
use Shlinkio\Shlink\CLI\Input\VisitsListInput;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(GetShortUrlVisitsCommand::NAME, 'Returns the detailed visits information for provided short code')]
class GetShortUrlVisitsCommand extends Command
{
    public const string NAME = 'short-url:visits';

    public function __construct(protected readonly VisitsStatsHelperInterface $visitsHelper)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The short code which visits we want to get'), Ask('Which short code do you want to use?')]
        string $shortCode,
        #[MapInput] VisitsListInput $input,
        #[Option('The domain for the short code', shortcut: 'd')]
        string|null $domain = null,
    ): int {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain);
        $dateRange = $input->dateRange();
        $paginator = $this->visitsHelper->visitsForShortUrl($identifier, new VisitsParams($dateRange));
        [$rows, $headers] = VisitsCommandUtils::resolveRowsAndHeaders($paginator);

        ShlinkTable::default($io)->render($headers, $rows);

        return self::SUCCESS;
    }
}

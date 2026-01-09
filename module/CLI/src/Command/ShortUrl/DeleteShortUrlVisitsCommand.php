<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Util\CommandUtils;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlVisitsDeleterInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(DeleteShortUrlVisitsCommand::NAME, 'Deletes visits from a short URL')]
class DeleteShortUrlVisitsCommand extends Command
{
    public const string NAME = 'short-url:visits-delete';

    public function __construct(private readonly ShortUrlVisitsDeleterInterface $deleter)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The short code for the short URL which visits will be deleted')] string $shortCode,
        #[Option('The domain if the short code does not belong to the default one', shortcut: 'd')]
        string|null $domain = null,
    ): int {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain);
        return CommandUtils::executeWithWarning(
            'You are about to delete all visits for a short URL. This operation cannot be undone',
            $io,
            fn () => $this->deleteVisits($io, $identifier),
        );
    }

    private function deleteVisits(SymfonyStyle $io, ShortUrlIdentifier $identifier): int
    {
        try {
            $result = $this->deleter->deleteShortUrlVisits($identifier);
            $io->success(sprintf('Successfully deleted %s visits', $result->affectedItems));

            return self::SUCCESS;
        } catch (ShortUrlNotFoundException) {
            $io->warning(sprintf('Short URL not found for "%s"', $identifier->__toString()));
            return self::INVALID;
        }
    }
}

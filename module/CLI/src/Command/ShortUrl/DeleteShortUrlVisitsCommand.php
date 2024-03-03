<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Visit\AbstractDeleteVisitsCommand;
use Shlinkio\Shlink\CLI\Input\ShortUrlIdentifierInput;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlVisitsDeleterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DeleteShortUrlVisitsCommand extends AbstractDeleteVisitsCommand
{
    public const NAME = 'short-url:visits-delete';

    private readonly ShortUrlIdentifierInput $shortUrlIdentifierInput;

    public function __construct(private readonly ShortUrlVisitsDeleterInterface $deleter)
    {
        parent::__construct();
        $this->shortUrlIdentifierInput = new ShortUrlIdentifierInput(
            $this,
            shortCodeDesc: 'The short code for the short URL which visits will be deleted',
            domainDesc: 'The domain if the short code does not belong to the default one',
        );
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deletes visits from a short URL');
    }

    protected function doExecute(InputInterface $input, SymfonyStyle $io): int
    {
        $identifier = $this->shortUrlIdentifierInput->toShortUrlIdentifier($input);
        try {
            $result = $this->deleter->deleteShortUrlVisits($identifier);
            $io->success(sprintf('Successfully deleted %s visits', $result->affectedItems));

            return ExitCode::EXIT_SUCCESS;
        } catch (ShortUrlNotFoundException) {
            $io->warning(sprintf('Short URL not found for "%s"', $identifier->__toString()));
            return ExitCode::EXIT_WARNING;
        }
    }

    protected function getWarningMessage(): string
    {
        return 'You are about to delete all visits for a short URL. This operation cannot be undone.';
    }
}

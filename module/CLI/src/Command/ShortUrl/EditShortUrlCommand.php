<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Input\ShortUrlDataInput;
use Shlinkio\Shlink\CLI\Input\ShortUrlIdentifierInput;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class EditShortUrlCommand extends Command
{
    public const string NAME = 'short-url:edit';

    private readonly ShortUrlDataInput $shortUrlDataInput;
    private readonly ShortUrlIdentifierInput $shortUrlIdentifierInput;

    public function __construct(
        private readonly ShortUrlServiceInterface $shortUrlService,
        private readonly ShortUrlStringifierInterface $stringifier,
    ) {
        parent::__construct();

        $this->shortUrlDataInput = new ShortUrlDataInput($this, longUrlAsOption: true);
        $this->shortUrlIdentifierInput = new ShortUrlIdentifierInput(
            $this,
            shortCodeDesc: 'The short code to edit',
            domainDesc: 'The domain to which the short URL is attached.',
        );
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Edit an existing short URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = $this->shortUrlIdentifierInput->toShortUrlIdentifier($input);

        try {
            $shortUrl = $this->shortUrlService->updateShortUrl(
                $identifier,
                $this->shortUrlDataInput->toShortUrlEdition($input),
            );

            $io->success(sprintf('Short URL "%s" properly edited', $this->stringifier->stringify($shortUrl)));
            return ExitCode::EXIT_SUCCESS;
        } catch (ShortUrlNotFoundException $e) {
            $io->error(sprintf('Short URL not found for "%s"', $identifier->__toString()));

            if ($io->isVerbose()) {
                $this->getApplication()?->renderThrowable($e, $io);
            }

            return ExitCode::EXIT_FAILURE;
        }
    }
}

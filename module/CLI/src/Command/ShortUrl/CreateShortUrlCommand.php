<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\ShortUrl\Input\ShortUrlCreationInput;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortenerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: CreateShortUrlCommand::NAME,
    description: 'Generates a short URL for provided long URL and returns it',
)]
class CreateShortUrlCommand extends Command
{
    public const string NAME = 'short-url:create';

    public function __construct(
        private readonly UrlShortenerInterface $urlShortener,
        private readonly ShortUrlStringifierInterface $stringifier,
        private readonly UrlShortenerOptions $options,
    ) {
        parent::__construct();
    }

    public function __invoke(SymfonyStyle $io, #[MapInput] ShortUrlCreationInput $inputData): int
    {
        try {
            $result = $this->urlShortener->shorten($inputData->toShortUrlCreation($this->options));

            $result->onEventDispatchingError(static fn () => $io->isVerbose() && $io->warning(
                'Short URL properly created, but the real-time updates cannot be notified when generating the '
                    . 'short URL from the command line. Migrate to roadrunner in order to bypass this limitation.',
            ));

            $io->writeln([
                sprintf('Processed long URL: <info>%s</info>', $result->shortUrl->getLongUrl()),
                sprintf('Generated short URL: <info>%s</info>', $this->stringifier->stringify($result->shortUrl)),
            ]);
            return self::SUCCESS;
        } catch (NonUniqueSlugException $e) {
            $io->error($e->getMessage());
            return self::FAILURE;
        }
    }
}

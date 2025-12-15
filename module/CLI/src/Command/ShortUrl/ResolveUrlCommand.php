<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(ResolveUrlCommand::NAME, 'Returns the long URL behind a short code')]
class ResolveUrlCommand extends Command
{
    public const string NAME = 'short-url:resolve';

    public function __construct(private readonly ShortUrlResolverInterface $urlResolver)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[
            Argument('The short code to resolve'),
            Ask('A short code was not provided. Which short code do you want to resolve?'),
        ]
        string $shortCode,
        #[Option('The domain to which the short URL is attached', shortcut: 'd')] string|null $domain = null,
    ): int {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain);

        try {
            $url = $this->urlResolver->resolveShortUrl($identifier);
            $io->writeln(sprintf('Long URL: <info>%s</info>', $url->getLongUrl()));
            return self::SUCCESS;
        } catch (ShortUrlNotFoundException $e) {
            $io->error($e->getMessage());
            return self::FAILURE;
        }
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\ShortUrl\Input\ShortUrlDataInput;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlServiceInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: EditShortUrlCommand::NAME,
    description: 'Edit an existing short URL',
)]
class EditShortUrlCommand extends Command
{
    public const string NAME = 'short-url:edit';

    public function __construct(
        private readonly ShortUrlServiceInterface $shortUrlService,
        private readonly ShortUrlStringifierInterface $stringifier,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput] ShortUrlDataInput $data,
        #[Argument('The short code to edit')] string $shortCode,
        #[Option('The domain to which the short URL is attached', shortcut: 'd')] string|null $domain = null,
        #[Option('The long URL to set', shortcut: 'l')] string|null $longUrl = null,
    ): int {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain);

        try {
            $shortUrl = $this->shortUrlService->updateShortUrl(
                $identifier,
                ShortUrlEdition::fromRawData($data->toArray()),
            );

            $io->success(sprintf('Short URL "%s" properly edited', $this->stringifier->stringify($shortUrl)));
            return self::SUCCESS;
        } catch (ShortUrlNotFoundException $e) {
            $io->error(sprintf('Short URL not found for "%s"', $identifier->__toString()));

            if ($io->isVerbose()) {
                $this->getApplication()?->renderThrowable($e, $io);
            }

            return self::FAILURE;
        }
    }
}

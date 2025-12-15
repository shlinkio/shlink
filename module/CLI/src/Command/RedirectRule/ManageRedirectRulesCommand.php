<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\RedirectRule;

use Shlinkio\Shlink\CLI\RedirectRule\RedirectRuleHandlerInterface;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: ManageRedirectRulesCommand::NAME,
    description: 'Set redirect rules for a short URL',
)]
class ManageRedirectRulesCommand extends Command
{
    public const string NAME = 'short-url:manage-rules';

    public function __construct(
        protected readonly ShortUrlResolverInterface $shortUrlResolver,
        protected readonly ShortUrlRedirectRuleServiceInterface $ruleService,
        protected readonly RedirectRuleHandlerInterface $ruleHandler,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The short code which rules we want to set')] string $shortCode,
        #[Option('The domain of the short code', shortcut: 'd')] string|null $domain = null,
    ): int {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain);

        try {
            $shortUrl = $this->shortUrlResolver->resolveShortUrl($identifier);
        } catch (ShortUrlNotFoundException) {
            $io->error(sprintf('Short URL for %s not found', $identifier->__toString()));
            return self::FAILURE;
        }

        $rulesToSave = $this->ruleHandler->manageRules($io, $shortUrl, $this->ruleService->rulesForShortUrl($shortUrl));
        if ($rulesToSave !== null) {
            $this->ruleService->saveRulesForShortUrl($shortUrl, $rulesToSave);
            $io->success('Rules properly saved');
        }

        return self::SUCCESS;
    }
}

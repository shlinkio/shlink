<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\RedirectRule;

use Shlinkio\Shlink\CLI\Input\ShortUrlIdentifierInput;
use Shlinkio\Shlink\CLI\RedirectRule\RedirectRuleHandlerInterface;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class ManageRedirectRulesCommand extends Command
{
    public const NAME = 'short-url:manage-rules';

    private readonly ShortUrlIdentifierInput $shortUrlIdentifierInput;

    public function __construct(
        protected readonly ShortUrlResolverInterface $shortUrlResolver,
        protected readonly ShortUrlRedirectRuleServiceInterface $ruleService,
        protected readonly RedirectRuleHandlerInterface $ruleHandler,
    ) {
        parent::__construct();
        $this->shortUrlIdentifierInput = new ShortUrlIdentifierInput(
            $this,
            shortCodeDesc: 'The short code which rules we want to set.',
            domainDesc: 'The domain for the short code.',
        );
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Set redirect rules for a short URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = $this->shortUrlIdentifierInput->toShortUrlIdentifier($input);

        try {
            $shortUrl = $this->shortUrlResolver->resolveShortUrl($identifier);
        } catch (ShortUrlNotFoundException) {
            $io->error(sprintf('Short URL for %s not found', $identifier->__toString()));
            return ExitCode::EXIT_FAILURE;
        }

        $rulesToSave = $this->ruleHandler->manageRules($io, $shortUrl, $this->ruleService->rulesForShortUrl($shortUrl));
        if ($rulesToSave !== null) {
            $this->ruleService->saveRulesForShortUrl($shortUrl, $rulesToSave);
            $io->success('Rules properly saved');
        }

        return ExitCode::EXIT_SUCCESS;
    }
}

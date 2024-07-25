<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use function array_map;
use function array_unique;
use function Shlinkio\Shlink\Core\ArrayUtils\flatten;
use function Shlinkio\Shlink\Core\splitByComma;

readonly final class ShortUrlDataInput
{
    public function __construct(Command $command, private bool $longUrlAsOption = false)
    {
        if ($longUrlAsOption) {
            $command->addOption('long-url', 'l', InputOption::VALUE_REQUIRED, 'The long URL to set');
        } else {
            $command->addArgument('longUrl', InputArgument::REQUIRED, 'The long URL to set');
        }

        $command
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Tags to apply to the short URL',
            )
            ->addOption(
                'valid-since',
                's',
                InputOption::VALUE_REQUIRED,
                'The date from which this short URL will be valid. '
                . 'If someone tries to access it before this date, it will not be found.',
            )
            ->addOption(
                'valid-until',
                'u',
                InputOption::VALUE_REQUIRED,
                'The date until which this short URL will be valid. '
                . 'If someone tries to access it after this date, it will not be found.',
            )
            ->addOption(
                'max-visits',
                'm',
                InputOption::VALUE_REQUIRED,
                'This will limit the number of visits for this short URL.',
            )
            ->addOption(
                'crawlable',
                'r',
                InputOption::VALUE_NONE,
                'Tells if this short URL will be included as "Allow" in Shlink\'s robots.txt.',
            )
            ->addOption(
                'no-forward-query',
                'w',
                InputOption::VALUE_NONE,
                'Disables the forwarding of the query string to the long URL, when the short URL is visited.',
            );
    }

    public function longUrl(InputInterface $input): ?string
    {
        return $this->longUrlAsOption ? $input->getOption('long-url') : $input->getArgument('longUrl');
    }

    /**
     * @return string[]
     */
    public function tags(InputInterface $input): array
    {
        return array_unique(flatten(array_map(splitByComma(...), $input->getOption('tags'))));
    }

    public function validSince(InputInterface $input): ?string
    {
        return $input->getOption('valid-since');
    }

    public function validUntil(InputInterface $input): ?string
    {
        return $input->getOption('valid-until');
    }

    public function maxVisits(InputInterface $input): ?int
    {
        $maxVisits = $input->getOption('max-visits');
        return $maxVisits !== null ? (int) $maxVisits : null;
    }

    public function crawlable(InputInterface $input): bool
    {
        return $input->getOption('crawlable');
    }

    public function noForwardQuery(InputInterface $input): bool
    {
        return $input->getOption('no-forward-query');
    }

    public function toShortUrlEdition(InputInterface $input): ShortUrlEdition
    {
        return ShortUrlEdition::fromRawData([
            ShortUrlInputFilter::LONG_URL => $this->longUrl($input),
            ShortUrlInputFilter::VALID_SINCE => $this->validSince($input),
            ShortUrlInputFilter::VALID_UNTIL => $this->validUntil($input),
            ShortUrlInputFilter::MAX_VISITS => $this->maxVisits($input),
            ShortUrlInputFilter::TAGS => $this->tags($input),
            ShortUrlInputFilter::CRAWLABLE => $this->crawlable($input),
            ShortUrlInputFilter::FORWARD_QUERY => !$this->noForwardQuery($input),
//            ShortUrlInputFilter::TITLE => TODO,
        ]);
    }

    // TODO
    // public function toShortUrlCreation(InputInterface $input)
}

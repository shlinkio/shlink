<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
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
                'title',
                mode: InputOption::VALUE_REQUIRED,
                description: 'A descriptive title for the short URL.',
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

    public function toShortUrlEdition(InputInterface $input): ShortUrlEdition
    {
        return ShortUrlEdition::fromRawData($this->getCommonData($input));
    }

    public function toShortUrlCreation(
        InputInterface $input,
        UrlShortenerOptions $options,
        string $customSlugField,
        string $shortCodeLengthField,
        string $pathPrefixField,
        string $findIfExistsField,
        string $domainField,
    ): ShortUrlCreation {
        $shortCodeLength = $input->getOption($shortCodeLengthField) ?? $options->defaultShortCodesLength;
        return ShortUrlCreation::fromRawData([
            ...$this->getCommonData($input),
            ShortUrlInputFilter::CUSTOM_SLUG => $input->getOption($customSlugField),
            ShortUrlInputFilter::SHORT_CODE_LENGTH => $shortCodeLength,
            ShortUrlInputFilter::PATH_PREFIX => $input->getOption($pathPrefixField),
            ShortUrlInputFilter::FIND_IF_EXISTS => $input->getOption($findIfExistsField),
            ShortUrlInputFilter::DOMAIN => $input->getOption($domainField),
        ], $options);
    }

    private function getCommonData(InputInterface $input): array
    {
        $longUrl = $this->longUrlAsOption ? $input->getOption('long-url') : $input->getArgument('longUrl');
        $data = [ShortUrlInputFilter::LONG_URL => $longUrl];

        // Avoid setting arguments that were not explicitly provided.
        // This is important when editing short URLs and should not make a difference when creating.
        if ($input->hasParameterOption(['--valid-since', '-s'])) {
            $data[ShortUrlInputFilter::VALID_SINCE] = $input->getOption('valid-since');
        }
        if ($input->hasParameterOption(['--valid-until', '-v'])) {
            $data[ShortUrlInputFilter::VALID_UNTIL] = $input->getOption('valid-until');
        }
        if ($input->hasParameterOption(['--max-visits', '-m'])) {
            $maxVisits = $input->getOption('max-visits');
            $data[ShortUrlInputFilter::MAX_VISITS] = $maxVisits !== null ? (int) $maxVisits : null;
        }
        if ($input->hasParameterOption(['--tags', '-t'])) {
            $tags = array_unique(flatten(array_map(splitByComma(...), $input->getOption('tags'))));
            $data[ShortUrlInputFilter::TAGS] = $tags;
        }
        if ($input->hasParameterOption('--title')) {
            $data[ShortUrlInputFilter::TITLE] = $input->getOption('title');
        }
        if ($input->hasParameterOption(['--crawlable', '-r'])) {
            $data[ShortUrlInputFilter::CRAWLABLE] = $input->getOption('crawlable');
        }
        if ($input->hasParameterOption(['--no-forward-query', '-w'])) {
            $data[ShortUrlInputFilter::FORWARD_QUERY] = !$input->getOption('no-forward-query');
        }

        return $data;
    }
}

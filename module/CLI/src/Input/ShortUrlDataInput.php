<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final readonly class ShortUrlDataInput
{
    private readonly TagsOption $tagsOption;

    public function __construct(Command $command, private bool $longUrlAsOption = false)
    {
        if ($longUrlAsOption) {
            $command->addOption('long-url', 'l', InputOption::VALUE_REQUIRED, 'The long URL to set');
        } else {
            $command->addArgument('longUrl', InputArgument::REQUIRED, 'The long URL to set');
        }

        $this->tagsOption = new TagsOption($command, 'Tags to apply to the short URL');

        $command
            ->addOption(
                ShortUrlDataOption::VALID_SINCE->value,
                ShortUrlDataOption::VALID_SINCE->shortcut(),
                InputOption::VALUE_REQUIRED,
                'The date from which this short URL will be valid. '
                . 'If someone tries to access it before this date, it will not be found.',
            )
            ->addOption(
                ShortUrlDataOption::VALID_UNTIL->value,
                ShortUrlDataOption::VALID_UNTIL->shortcut(),
                InputOption::VALUE_REQUIRED,
                'The date until which this short URL will be valid. '
                . 'If someone tries to access it after this date, it will not be found.',
            )
            ->addOption(
                ShortUrlDataOption::MAX_VISITS->value,
                ShortUrlDataOption::MAX_VISITS->shortcut(),
                InputOption::VALUE_REQUIRED,
                'This will limit the number of visits for this short URL.',
            )
            ->addOption(
                ShortUrlDataOption::TITLE->value,
                ShortUrlDataOption::TITLE->shortcut(),
                InputOption::VALUE_REQUIRED,
                'A descriptive title for the short URL.',
            )
            ->addOption(
                ShortUrlDataOption::CRAWLABLE->value,
                ShortUrlDataOption::CRAWLABLE->shortcut(),
                InputOption::VALUE_NONE,
                'Tells if this short URL will be included as "Allow" in Shlink\'s robots.txt.',
            )
            ->addOption(
                ShortUrlDataOption::NO_FORWARD_QUERY->value,
                ShortUrlDataOption::NO_FORWARD_QUERY->shortcut(),
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
        if (ShortUrlDataOption::VALID_SINCE->wasProvided($input)) {
            $data[ShortUrlInputFilter::VALID_SINCE] = $input->getOption('valid-since');
        }
        if (ShortUrlDataOption::VALID_UNTIL->wasProvided($input)) {
            $data[ShortUrlInputFilter::VALID_UNTIL] = $input->getOption('valid-until');
        }
        if (ShortUrlDataOption::MAX_VISITS->wasProvided($input)) {
            $maxVisits = $input->getOption('max-visits');
            $data[ShortUrlInputFilter::MAX_VISITS] = $maxVisits !== null ? (int) $maxVisits : null;
        }
        if ($this->tagsOption->exists($input)) {
            $data[ShortUrlInputFilter::TAGS] = $this->tagsOption->get($input);
        }
        if (ShortUrlDataOption::TITLE->wasProvided($input)) {
            $data[ShortUrlInputFilter::TITLE] = $input->getOption('title');
        }
        if (ShortUrlDataOption::CRAWLABLE->wasProvided($input)) {
            $data[ShortUrlInputFilter::CRAWLABLE] = $input->getOption('crawlable');
        }
        if (ShortUrlDataOption::NO_FORWARD_QUERY->wasProvided($input)) {
            $data[ShortUrlInputFilter::FORWARD_QUERY] = !$input->getOption('no-forward-query');
        }

        return $data;
    }
}

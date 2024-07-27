<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Symfony\Component\Console\Input\InputInterface;

use function sprintf;

enum ShortUrlDataOption: string
{
    case TAGS = 'tags';
    case VALID_SINCE = 'valid-since';
    case VALID_UNTIL = 'valid-until';
    case MAX_VISITS = 'max-visits';
    case TITLE = 'title';
    case CRAWLABLE = 'crawlable';
    case NO_FORWARD_QUERY = 'no-forward-query';

    public function shortcut(): ?string
    {
        return match ($this) {
            self::TAGS => 't',
            self::VALID_SINCE => 's',
            self::VALID_UNTIL => 'u',
            self::MAX_VISITS => 'm',
            self::TITLE => null,
            self::CRAWLABLE => 'r',
            self::NO_FORWARD_QUERY => 'w',
        };
    }

    public function wasProvided(InputInterface $input): bool
    {
        $option = sprintf('--%s', $this->value);
        $shortcut = $this->shortcut();

        return $input->hasParameterOption($shortcut === null ? $option : [$option, sprintf('-%s', $shortcut)]);
    }
}

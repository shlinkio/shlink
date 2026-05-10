<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl\Input;

use Symfony\Component\Console\Attribute\Option;

use function array_filter;
use function array_unique;
use function get_object_vars;

/**
 * Common input used for short URL creation and edition
 */
final class ShortUrlDataInput
{
    /** @var string[]|null */
    #[Option('Tags to apply to the short URL', name: 'tag', shortcut: 't')]
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Multiple
    public array|null $tags = null {
        // phpcs:disable PSR2.Classes.PropertyDeclaration.Multiple, PSR2.Classes.PropertyDeclaration.ScopeMissing
        set(array | null $value) => $value !== null ? array_unique($value) : $value;
    }

    #[Option(
        'The date from which this short URL will be valid. '
        . 'If someone tries to access it before this date, it will not be found',
        shortcut: 's',
    )]
    public string|null $validSince = null;

    #[Option(
        'The date until which this short URL will be valid. '
        . 'If someone tries to access it after this date, it will not be found',
        shortcut: 'u',
    )]
    public string|null $validUntil = null;

    #[Option('This will limit the number of visits for this short URL', shortcut: 'm')]
    public int|null $maxVisits = null;

    #[Option('A descriptive title for the short URL')]
    public string|null $title = null;

    #[Option('Tells if this short URL will be included as "Allow" in Shlink\'s robots.txt', shortcut: 'r')]
    public bool|null $crawlable = null;

    #[Option(
        'Disables the forwarding of the query string to the long URL, when the short URL is visited',
        shortcut: 'w',
    )]
    public bool|null $noForwardQuery = null;

    public function toArray(string|null $longUrl): array
    {
        return [
            ...array_filter(get_object_vars($this), static fn (mixed $value) => $value !== null),
            'longUrl' => $longUrl,
        ];
    }
}

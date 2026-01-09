<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl\Input;

use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Symfony\Component\Console\Attribute\Option;

use function array_unique;

/**
 * Common input used for short URL creation and edition
 */
final class ShortUrlDataInput
{
    /** @var string[]|null */
    #[Option('Tags to apply to the short URL', name: 'tag', shortcut: 't')]
    public array|null $tags = null;

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

    public function toArray(): array
    {
        $data = [];

        // Avoid setting arguments that were not explicitly provided.
        // This is important when editing short URLs and should not make a difference when creating.
        if ($this->validSince !== null) {
            $data[ShortUrlInputFilter::VALID_SINCE] = $this->validSince;
        }
        if ($this->validUntil !== null) {
            $data[ShortUrlInputFilter::VALID_UNTIL] = $this->validUntil;
        }
        if ($this->maxVisits !== null) {
            $data[ShortUrlInputFilter::MAX_VISITS] = $this->maxVisits;
        }
        if ($this->tags !== null) {
            $data[ShortUrlInputFilter::TAGS] = array_unique($this->tags);
        }
        if ($this->title !== null) {
            $data[ShortUrlInputFilter::TITLE] = $this->title;
        }
        if ($this->crawlable !== null) {
            $data[ShortUrlInputFilter::CRAWLABLE] = $this->crawlable;
        }
        if ($this->noForwardQuery !== null) {
            $data[ShortUrlInputFilter::FORWARD_QUERY] = !$this->noForwardQuery;
        }

        return $data;
    }
}

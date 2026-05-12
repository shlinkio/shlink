<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl\Input;

use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;

use function array_filter;
use function get_object_vars;
use function max;

use const ARRAY_FILTER_USE_KEY;
use const Shlinkio\Shlink\MIN_SHORT_CODES_LENGTH;

/**
 * Data used for short URL creation
 */
final class ShortUrlCreationInput
{
    #[Argument('The long URL to set'), Ask('Which URL do you want to shorten?')]
    public string $longUrl;

    #[MapInput]
    public ShortUrlDataInput $commonData;

    #[Option('The domain to which this short URL will be attached', shortcut: 'd')]
    public string|null $domain = null;

    #[Option('If provided, this slug will be used instead of generating a short code', shortcut: 'c')]
    public string|null $customSlug = null;

    #[Option('The length for generated short code (it will be ignored if --custom-slug was provided)', shortcut: 'l')]
    public int|null $shortCodeLength = null;

    #[Option('Prefix to prepend before the generated short code or provided custom slug', shortcut: 'p')]
    public string|null $pathPrefix = null;

    #[Option(
        'This will force existing matching URL to be returned if found, instead of creating a new one',
        shortcut: 'f',
    )]
    public bool $findIfExists = false;

    public function toArray(UrlShortenerOptions $options): array
    {
        $common = $this->commonData->toArray($this->longUrl);
        $creation = array_filter(
            get_object_vars($this),
            static fn (string $key) => $key !== 'commonData',
            ARRAY_FILTER_USE_KEY,
        );
        $shortCodeLength = max(MIN_SHORT_CODES_LENGTH, $this->shortCodeLength ?? $options->defaultShortCodesLength);

        return [
            ...$common,
            ...$creation,
            'shortCodeLength' => $shortCodeLength,
            'shortUrlMode' => $options->mode,
            'multiSegmentSlugsEnabled' => $options->multiSegmentSlugsEnabled,
        ];
    }
}

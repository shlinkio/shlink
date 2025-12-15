<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl\Input;

use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;

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

    public function toShortUrlCreation(UrlShortenerOptions $options): ShortUrlCreation
    {
        $shortCodeLength = $this->shortCodeLength ?? $options->defaultShortCodesLength;
        return ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => $this->longUrl,
            ShortUrlInputFilter::DOMAIN => $this->domain,
            ShortUrlInputFilter::CUSTOM_SLUG => $this->customSlug,
            ShortUrlInputFilter::SHORT_CODE_LENGTH => $shortCodeLength,
            ShortUrlInputFilter::PATH_PREFIX => $this->pathPrefix,
            ShortUrlInputFilter::FIND_IF_EXISTS => $this->findIfExists,
            ...$this->commonData->toArray(),
        ], $options);
    }
}

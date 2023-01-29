<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use Laminas\Filter\FilterInterface;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;

use function is_string;
use function str_replace;
use function strtolower;
use function trim;

class CustomSlugFilter implements FilterInterface
{
    public function __construct(private readonly UrlShortenerOptions $options)
    {
    }

    public function filter(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = $this->options->isLooseMode() ? strtolower($value) : $value;
        return (match ($this->options->multiSegmentSlugsEnabled) {
            true => trim(str_replace(' ', '-', $value), '/'),
            false => str_replace([' ', '/'], '-', $value),
        });
    }
}

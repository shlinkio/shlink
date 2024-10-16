<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use Laminas\Filter\FilterInterface;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;

use function is_string;
use function str_replace;
use function strtolower;
use function trim;

readonly class CustomSlugFilter implements FilterInterface
{
    public function __construct(private UrlShortenerOptions $options)
    {
    }

    public function filter(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = $this->options->isLooseMode() ? strtolower($value) : $value;
        return $this->options->multiSegmentSlugsEnabled
            ? trim(str_replace(' ', '-', $value), '/')
            : str_replace([' ', '/'], '-', $value);
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use Laminas\Validator\AbstractValidator;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;

use function is_string;
use function strpbrk;

class CustomSlugValidator extends AbstractValidator
{
    private const NOT_STRING = 'NOT_STRING';
    private const CONTAINS_URL_CHARACTERS = 'CONTAINS_URL_CHARACTERS';

    protected array $messageTemplates = [
        self::NOT_STRING => 'Provided value is not a string.',
        self::CONTAINS_URL_CHARACTERS => 'URL-reserved characters cannot be used in a custom slug.',
    ];

    private UrlShortenerOptions $options;

    private function __construct()
    {
        parent::__construct();
    }

    public static function forUrlShortenerOptions(UrlShortenerOptions $options): self
    {
        $instance = new self();
        $instance->options = $options;

        return $instance;
    }

    public function isValid(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_string($value)) {
            $this->error(self::NOT_STRING);
            return false;
        }

        // URL reserved characters: https://datatracker.ietf.org/doc/html/rfc3986#section-2.2
        $reservedChars = "!*'();:@&=+$,?%#[]";
        if (! $this->options->multiSegmentSlugsEnabled) {
            // Slashes should be allowed for multi-segment slugs
            $reservedChars .= '/';
        }

        if (strpbrk($value, $reservedChars) !== false) {
            $this->error(self::CONTAINS_URL_CHARACTERS);
            return false;
        }

        return true;
    }
}

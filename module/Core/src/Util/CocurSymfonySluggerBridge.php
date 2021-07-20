<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Cocur\Slugify\SlugifyInterface;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class CocurSymfonySluggerBridge implements SluggerInterface
{
    public function __construct(private SlugifyInterface $slugger)
    {
    }

    public function slug(string $string, string $separator = '-', ?string $locale = null): AbstractUnicodeString
    {
        return new UnicodeString($this->slugger->slugify($string, $separator));
    }
}

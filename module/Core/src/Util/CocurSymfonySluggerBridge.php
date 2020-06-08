<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Cocur\Slugify\SlugifyInterface;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;

use function Symfony\Component\String\s;

class CocurSymfonySluggerBridge implements SluggerInterface
{
    private SlugifyInterface $slugger;

    public function __construct(SlugifyInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function slug(string $string, string $separator = '-', ?string $locale = null): AbstractUnicodeString
    {
        return s($this->slugger->slugify($string, $separator));
    }
}

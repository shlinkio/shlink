<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

interface TitleResolutionModelInterface
{
    public string|null $longUrl { get; }

    public function hasTitle(): bool;

    public function withResolvedTitle(string $title): static;
}

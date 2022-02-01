<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

interface TitleResolutionModelInterface
{
    public function hasTitle(): bool;

    public function getLongUrl(): string;

    public function doValidateUrl(): bool;

    public function withResolvedTitle(string $title): self;
}

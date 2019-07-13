<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

final class ShortUrlVisited
{
    /** @var string */
    private $visitId;

    public function __construct(string $visitId)
    {
        $this->visitId = $visitId;
    }

    public function visitId(): string
    {
        return $this->visitId;
    }
}

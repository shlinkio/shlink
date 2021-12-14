<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Core\Exception\ValidationException;

use function array_pad;
use function explode;

final class ShortUrlsOrdering
{
    public const ORDER_BY = 'orderBy';
    private const DEFAULT_ORDER_DIRECTION = 'ASC';

    private ?string $orderField = null;
    private string $orderDirection = self::DEFAULT_ORDER_DIRECTION;

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $query): self
    {
        $instance = new self();
        $instance->validateAndInit($query);

        return $instance;
    }

    /**
     * @throws ValidationException
     */
    private function validateAndInit(array $data): void
    {
        $orderBy = $data[self::ORDER_BY] ?? null;
        if ($orderBy === null) {
            return;
        }

        [$field, $dir] = array_pad(explode('-', $orderBy), 2, null);
        $this->orderField = $field;
        $this->orderDirection = $dir ?? self::DEFAULT_ORDER_DIRECTION;
    }

    public function orderField(): ?string
    {
        return $this->orderField;
    }

    public function orderDirection(): string
    {
        return $this->orderDirection;
    }

    public function hasOrderField(): bool
    {
        return $this->orderField !== null;
    }
}

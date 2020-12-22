<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Core\Exception\ValidationException;

use function explode;
use function is_array;
use function is_string;
use function key;

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

        // FIXME Providing the ordering as array is considered deprecated. To be removed in v3.0.0
        $isArray = is_array($orderBy);
        if (! $isArray && ! is_string($orderBy)) {
            throw ValidationException::fromArray([
                'orderBy' => '"Order by" must be an array, string or null',
            ]);
        }

        /** @var string|array $orderBy */
        if (! $isArray) {
            $parts = explode('-', $orderBy);
            $this->orderField = $parts[0];
            $this->orderDirection = $parts[1] ?? self::DEFAULT_ORDER_DIRECTION;
        } else {
            $this->orderField = key($orderBy);
            $this->orderDirection = $orderBy[$this->orderField];
        }
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

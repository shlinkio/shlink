<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Core\Exception\ValidationException;

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
        /** @var string|array|null $orderBy */
        $orderBy = $data[self::ORDER_BY] ?? null;
        if ($orderBy === null) {
            return;
        }

        $isArray = is_array($orderBy);
        if (! $isArray && $orderBy !== null && ! is_string($orderBy)) {
            throw ValidationException::fromArray([
                'orderBy' => '"Order by" must be an array, string or null',
            ]);
        }

        $this->orderField = $isArray ? key($orderBy) : $orderBy;
        $this->orderDirection = $isArray ? $orderBy[$this->orderField] : self::DEFAULT_ORDER_DIRECTION;
    }

    public function orderField(): ?string
    {
        return $this->orderField;
    }

    public function orderDirection(): string
    {
        return $this->orderDirection;
    }
}

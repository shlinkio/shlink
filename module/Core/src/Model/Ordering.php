<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

final class Ordering
{
    private const DEFAULT_DIR = 'ASC';

    private function __construct(private ?string $field, private string $dir)
    {
    }

    /**
     * @param array{string|null, string|null} $props
     */
    public static function fromTuple(array $props): self
    {
        [$field, $dir] = $props;
        return new self($field, $dir ?? self::DEFAULT_DIR);
    }

    public static function emptyInstance(): self
    {
        return self::fromTuple([null, null]);
    }

    public function orderField(): ?string
    {
        return $this->field;
    }

    public function orderDirection(): string
    {
        return $this->dir;
    }

    public function hasOrderField(): bool
    {
        return $this->field !== null;
    }
}

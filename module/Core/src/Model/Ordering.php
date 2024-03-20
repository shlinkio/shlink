<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

final readonly class Ordering
{
    private const DESC_DIR = 'DESC';
    private const ASC_DIR = 'ASC';
    private const DEFAULT_DIR = self::ASC_DIR;

    private function __construct(public ?string $field, public string $direction)
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

    public static function none(): self
    {
        return new self(null, self::DEFAULT_DIR);
    }

    public static function fromFieldAsc(string $field): self
    {
        return new self($field, self::ASC_DIR);
    }

    public static function fromFieldDesc(string $field): self
    {
        return new self($field, self::DESC_DIR);
    }
}

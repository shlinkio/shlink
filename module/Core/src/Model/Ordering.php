<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

final readonly class Ordering
{
    private const string DESC_DIR = 'DESC';
    private const string ASC_DIR = 'ASC';
    private const string DEFAULT_DIR = self::ASC_DIR;

    public function __construct(public string|null $field = null, public string $direction = self::DEFAULT_DIR)
    {
    }

    public static function none(): self
    {
        return new self();
    }

    /**
     * @param array{string|null, string|null} $props
     */
    public static function fromTuple(array $props): self
    {
        [$field, $dir] = $props;
        return new self($field, $dir ?? self::DEFAULT_DIR);
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

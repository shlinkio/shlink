<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Entity;

abstract class AbstractEntity
{
    /** @var string */
    protected $id;

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @internal
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
}

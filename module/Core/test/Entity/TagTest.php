<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\Tag;

class TagTest extends TestCase
{
    /** @test */
    public function jsonSerializationOfTagsReturnsItsStringRepresentation(): void
    {
        $tag = new Tag('This is my name');
        $this->assertEquals((string) $tag, $tag->jsonSerialize());
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Tag\Entity;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;

class TagTest extends TestCase
{
    /** @test */
    public function jsonSerializationOfTagsReturnsItsStringRepresentation(): void
    {
        $tag = new Tag('This is my name');
        self::assertEquals((string) $tag, $tag->jsonSerialize());
    }
}

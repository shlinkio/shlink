<?php
namespace ShlinkioTest\Shlink\Core\Entity;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\Tag;

class TagTest extends TestCase
{
    /**
     * @test
     */
    public function jsonSerializationOfTagsReturnsItsName()
    {
        $tag = new Tag();
        $tag->setName('This is my name');
        $this->assertEquals($tag->getName(), $tag->jsonSerialize());
    }
}

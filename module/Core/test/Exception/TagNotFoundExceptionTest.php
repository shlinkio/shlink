<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;

use function sprintf;

class TagNotFoundExceptionTest extends TestCase
{
    /** @test */
    public function properlyCreatesExceptionFromNotFoundTag(): void
    {
        $tag = 'foo';
        $expectedMessage = sprintf('Tag with name "%s" could not be found', $tag);
        $e = TagNotFoundException::fromTag($tag);

        $this->assertEquals($expectedMessage, $e->getMessage());
        $this->assertEquals($expectedMessage, $e->getDetail());
        $this->assertEquals('Tag not found', $e->getTitle());
        $this->assertEquals('TAG_NOT_FOUND', $e->getType());
        $this->assertEquals(['tag' => $tag], $e->getAdditionalData());
        $this->assertEquals(404, $e->getStatus());
    }
}

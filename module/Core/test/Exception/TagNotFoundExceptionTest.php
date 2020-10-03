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

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Tag not found', $e->getTitle());
        self::assertEquals('TAG_NOT_FOUND', $e->getType());
        self::assertEquals(['tag' => $tag], $e->getAdditionalData());
        self::assertEquals(404, $e->getStatus());
    }
}

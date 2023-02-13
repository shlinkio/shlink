<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;

use function sprintf;

class TagNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function properlyCreatesExceptionFromNotFoundTag(): void
    {
        $tag = 'foo';
        $expectedMessage = sprintf('Tag with name "%s" could not be found', $tag);
        $e = TagNotFoundException::fromTag($tag);

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Tag not found', $e->getTitle());
        self::assertEquals('https://shlink.io/api/error/tag-not-found', $e->getType());
        self::assertEquals(['tag' => $tag], $e->getAdditionalData());
        self::assertEquals(404, $e->getStatus());
    }
}

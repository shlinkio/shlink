<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\TagConflictException;

use function sprintf;

class TagConflictExceptionTest extends TestCase
{
    /** @test */
    public function properlyCreatesExceptionFromNotFoundTag(): void
    {
        $oldName = 'foo';
        $newName = 'bar';
        $expectedMessage = sprintf('You cannot rename tag %s to %s, because it already exists', $oldName, $newName);
        $e = TagConflictException::fromExistingTag($oldName, $newName);

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Tag conflict', $e->getTitle());
        self::assertEquals('TAG_CONFLICT', $e->getType());
        self::assertEquals(['oldName' => $oldName, 'newName' => $newName], $e->getAdditionalData());
        self::assertEquals(409, $e->getStatus());
    }
}

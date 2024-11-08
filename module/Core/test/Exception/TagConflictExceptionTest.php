<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Model\Renaming;

use function sprintf;

class TagConflictExceptionTest extends TestCase
{
    #[Test]
    public function properlyCreatesExceptionForExistingTag(): void
    {
        $oldName = 'foo';
        $newName = 'bar';
        $expectedMessage = sprintf('You cannot rename tag %s to %s, because it already exists', $oldName, $newName);
        $e = TagConflictException::forExistingTag(Renaming::fromNames($oldName, $newName));

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Tag conflict', $e->getTitle());
        self::assertEquals('https://shlink.io/api/error/tag-conflict', $e->getType());
        self::assertEquals(['oldName' => $oldName, 'newName' => $newName], $e->getAdditionalData());
        self::assertEquals(409, $e->getStatus());
    }
}

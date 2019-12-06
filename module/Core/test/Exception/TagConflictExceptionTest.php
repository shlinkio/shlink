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

        $this->assertEquals($expectedMessage, $e->getMessage());
        $this->assertEquals($expectedMessage, $e->getDetail());
        $this->assertEquals('Tag conflict', $e->getTitle());
        $this->assertEquals('TAG_CONFLICT', $e->getType());
        $this->assertEquals(['oldName' => $oldName, 'newName' => $newName], $e->getAdditionalData());
        $this->assertEquals(409, $e->getStatus());
    }
}

<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Tag;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepository;
use Shlinkio\Shlink\Core\Tag\TagService;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyHelpersTrait;

class TagServiceTest extends TestCase
{
    use ApiKeyHelpersTrait;

    private TagService $service;
    private MockObject & EntityManagerInterface $em;
    private MockObject & TagRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(TagRepository::class);
        $this->em->method('getRepository')->with(Tag::class)->willReturn($this->repo);

        $this->service = new TagService($this->em);
    }

    /** @test */
    public function listTagsDelegatesOnRepository(): void
    {
        $expected = [new Tag('foo'), new Tag('bar')];

        $this->repo->expects($this->once())->method('match')->willReturn($expected);
        $this->repo->expects($this->once())->method('matchSingleScalarResult')->willReturn(2);

        $result = $this->service->listTags(TagsParams::fromRawData([]));

        self::assertEquals($expected, $result->getCurrentPageResults());
    }

    /**
     * @test
     * @dataProvider provideApiKeysAndSearchTerm
     */
    public function tagsInfoDelegatesOnRepository(
        ?ApiKey $apiKey,
        TagsParams $params,
        TagsListFiltering $expectedFiltering,
        int $countCalls,
    ): void {
        $expected = [new TagInfo('foo', 1, 1), new TagInfo('bar', 3, 10)];

        $this->repo->expects($this->once())->method('findTagsWithInfo')->with($expectedFiltering)->willReturn(
            $expected,
        );
        $this->repo->expects($this->exactly($countCalls))->method('matchSingleScalarResult')->willReturn(2);

        $result = $this->service->tagsInfo($params, $apiKey);

        self::assertEquals($expected, $result->getCurrentPageResults());
    }

    public function provideApiKeysAndSearchTerm(): iterable
    {
        yield 'no API key, no filter' => [
            null,
            $params = TagsParams::fromRawData([]),
            TagsListFiltering::fromRangeAndParams(2, 0, $params, null),
            1,
        ];
        yield 'admin API key, no filter' => [
            $apiKey = ApiKey::create(),
            $params = TagsParams::fromRawData([]),
            TagsListFiltering::fromRangeAndParams(2, 0, $params, $apiKey),
            1,
        ];
        yield 'no API key, search term' => [
            null,
            $params = TagsParams::fromRawData(['searchTerm' => 'foobar']),
            TagsListFiltering::fromRangeAndParams(2, 0, $params, null),
            1,
        ];
        yield 'admin API key, limits' => [
            $apiKey = ApiKey::create(),
            $params = TagsParams::fromRawData(['page' => 1, 'itemsPerPage' => 1]),
            TagsListFiltering::fromRangeAndParams(1, 0, $params, $apiKey),
            0,
        ];
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function deleteTagsDelegatesOnRepository(?ApiKey $apiKey): void
    {
        $this->repo->expects($this->once())->method('deleteByName')->with(['foo', 'bar'])->willReturn(4);
        $this->service->deleteTags(['foo', 'bar'], $apiKey);
    }

    /** @test */
    public function deleteTagsThrowsExceptionWhenProvidedApiKeyIsNotAdmin(): void
    {
        $this->repo->expects($this->never())->method('deleteByName');

        $this->expectException(ForbiddenTagOperationException::class);
        $this->expectExceptionMessage('You are not allowed to delete tags');

        $this->service->deleteTags(
            ['foo', 'bar'],
            ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls())),
        );
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function renameInvalidTagThrowsException(?ApiKey $apiKey): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->willReturn(null);
        $this->expectException(TagNotFoundException::class);

        $this->service->renameTag(TagRenaming::fromNames('foo', 'bar'), $apiKey);
    }

    /**
     * @test
     * @dataProvider provideValidRenames
     */
    public function renameValidTagChangesItsName(string $oldName, string $newName, int $count): void
    {
        $expected = new Tag('foo');

        $this->repo->expects($this->once())->method('findOneBy')->willReturn($expected);
        $this->repo->expects($this->exactly($count > 0 ? 0 : 1))->method('count')->willReturn($count);
        $this->em->expects($this->once())->method('flush');

        $tag = $this->service->renameTag(TagRenaming::fromNames($oldName, $newName));

        self::assertSame($expected, $tag);
        self::assertEquals($newName, (string) $tag);
    }

    public function provideValidRenames(): iterable
    {
        yield 'same names' => ['foo', 'foo', 1];
        yield 'different names names' => ['foo', 'bar', 0];
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function renameTagToAnExistingNameThrowsException(?ApiKey $apiKey): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->willReturn(new Tag('foo'));
        $this->repo->expects($this->once())->method('count')->willReturn(1);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(TagConflictException::class);

        $this->service->renameTag(TagRenaming::fromNames('foo', 'bar'), $apiKey);
    }

    /** @test */
    public function renamingTagThrowsExceptionWhenProvidedApiKeyIsNotAdmin(): void
    {
        $this->em->expects($this->never())->method('getRepository')->with(Tag::class);

        $this->expectExceptionMessage(ForbiddenTagOperationException::class);
        $this->expectExceptionMessage('You are not allowed to rename tags');

        $this->service->renameTag(
            TagRenaming::fromNames('foo', 'bar'),
            ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls())),
        );
    }
}

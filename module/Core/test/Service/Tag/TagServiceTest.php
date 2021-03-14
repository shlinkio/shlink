<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\Tag;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Core\Tag\TagService;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyHelpersTrait;

class TagServiceTest extends TestCase
{
    use ApiKeyHelpersTrait;
    use ProphecyTrait;

    private TagService $service;
    private ObjectProphecy $em;
    private ObjectProphecy $repo;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->repo = $this->prophesize(TagRepository::class);
        $this->em->getRepository(Tag::class)->willReturn($this->repo->reveal());

        $this->service = new TagService($this->em->reveal());
    }

    /** @test */
    public function listTagsDelegatesOnRepository(): void
    {
        $expected = [new Tag('foo'), new Tag('bar')];

        $match = $this->repo->match(Argument::cetera())->willReturn($expected);

        $result = $this->service->listTags();

        self::assertEquals($expected, $result);
        $match->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function tagsInfoDelegatesOnRepository(?ApiKey $apiKey): void
    {
        $expected = [new TagInfo(new Tag('foo'), 1, 1), new TagInfo(new Tag('bar'), 3, 10)];

        $find = $this->repo->findTagsWithInfo($apiKey)->willReturn($expected);

        $result = $this->service->tagsInfo($apiKey);

        self::assertEquals($expected, $result);
        $find->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function deleteTagsDelegatesOnRepository(?ApiKey $apiKey): void
    {
        $delete = $this->repo->deleteByName(['foo', 'bar'])->willReturn(4);

        $this->service->deleteTags(['foo', 'bar'], $apiKey);

        $delete->shouldHaveBeenCalled();
    }

    /** @test */
    public function deleteTagsThrowsExceptionWhenProvidedApiKeyIsNotAdmin(): void
    {
        $delete = $this->repo->deleteByName(['foo', 'bar']);

        $this->expectException(ForbiddenTagOperationException::class);
        $this->expectExceptionMessage('You are not allowed to delete tags');
        $delete->shouldNotBeCalled();

        $this->service->deleteTags(
            ['foo', 'bar'],
            ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls())),
        );
    }

    /** @test */
    public function createTagsPersistsEntities(): void
    {
        $find = $this->repo->findOneBy(Argument::cetera())->willReturn(new Tag('foo'));
        $persist = $this->em->persist(Argument::type(Tag::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $result = $this->service->createTags(['foo', 'bar']);

        self::assertCount(2, $result);
        $find->shouldHaveBeenCalled();
        $persist->shouldHaveBeenCalledTimes(2);
        $flush->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function renameInvalidTagThrowsException(?ApiKey $apiKey): void
    {
        $find = $this->repo->findOneBy(Argument::cetera())->willReturn(null);

        $find->shouldBeCalled();
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

        $find = $this->repo->findOneBy(Argument::cetera())->willReturn($expected);
        $countTags = $this->repo->count(Argument::cetera())->willReturn($count);
        $flush = $this->em->flush()->willReturn(null);

        $tag = $this->service->renameTag(TagRenaming::fromNames($oldName, $newName));

        self::assertSame($expected, $tag);
        self::assertEquals($newName, (string) $tag);
        $find->shouldHaveBeenCalled();
        $flush->shouldHaveBeenCalled();
        $countTags->shouldHaveBeenCalledTimes($count > 0 ? 0 : 1);
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
        $find = $this->repo->findOneBy(Argument::cetera())->willReturn(new Tag('foo'));
        $countTags = $this->repo->count(Argument::cetera())->willReturn(1);
        $flush = $this->em->flush(Argument::any())->willReturn(null);

        $find->shouldBeCalled();
        $countTags->shouldBeCalled();
        $flush->shouldNotBeCalled();
        $this->expectException(TagConflictException::class);

        $this->service->renameTag(TagRenaming::fromNames('foo', 'bar'), $apiKey);
    }

    /** @test */
    public function renamingTagThrowsExceptionWhenProvidedApiKeyIsNotAdmin(): void
    {
        $getRepo = $this->em->getRepository(Tag::class);

        $this->expectExceptionMessage(ForbiddenTagOperationException::class);
        $this->expectExceptionMessage('You are not allowed to rename tags');
        $getRepo->shouldNotBeCalled();

        $this->service->renameTag(
            TagRenaming::fromNames('foo', 'bar'),
            ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls())),
        );
    }
}

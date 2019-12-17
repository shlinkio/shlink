<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;

class TagsFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ShortUrlsFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $fooTag = new Tag('foo');
        $manager->persist($fooTag);
        $barTag = new Tag('bar');
        $manager->persist($barTag);

        /** @var ShortUrl $abcShortUrl */
        $abcShortUrl = $this->getReference('abc123_short_url');
        $abcShortUrl->setTags(new ArrayCollection([$fooTag]));

        /** @var ShortUrl $defShortUrl */
        $defShortUrl = $this->getReference('def456_short_url');
        $defShortUrl->setTags(new ArrayCollection([$fooTag, $barTag]));

        $manager->flush();
    }
}

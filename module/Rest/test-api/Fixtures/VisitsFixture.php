<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;

class VisitsFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ShortUrlsFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var ShortUrl $abcShortUrl */
        $abcShortUrl = $this->getReference('abc123_short_url');
        $manager->persist(
            Visit::forValidShortUrl($abcShortUrl, new Visitor('shlink-tests-agent', '', '44.55.66.77', '')),
        );
        $manager->persist(Visit::forValidShortUrl(
            $abcShortUrl,
            new Visitor('shlink-tests-agent', 'https://google.com', '4.5.6.7', ''),
        ));
        $manager->persist(Visit::forValidShortUrl($abcShortUrl, new Visitor('shlink-tests-agent', '', '1.2.3.4', '')));

        /** @var ShortUrl $defShortUrl */
        $defShortUrl = $this->getReference('def456_short_url');
        $manager->persist(
            Visit::forValidShortUrl($defShortUrl, new Visitor('shlink-tests-agent', '', '127.0.0.1', '')),
        );
        $manager->persist(
            Visit::forValidShortUrl($defShortUrl, new Visitor('shlink-tests-agent', 'https://app.shlink.io', '', '')),
        );

        /** @var ShortUrl $ghiShortUrl */
        $ghiShortUrl = $this->getReference('ghi789_short_url');
        $manager->persist(Visit::forValidShortUrl($ghiShortUrl, new Visitor('shlink-tests-agent', '', '1.2.3.4', '')));
        $manager->persist(
            Visit::forValidShortUrl($ghiShortUrl, new Visitor('shlink-tests-agent', 'https://app.shlink.io', '', '')),
        );

        $manager->persist(Visit::forBasePath(new Visitor('shlink-tests-agent', 'https://doma.in', '1.2.3.4', '')));
        $manager->persist(
            Visit::forRegularNotFound(new Visitor('shlink-tests-agent', 'https://doma.in/foo/bar', '1.2.3.4', '')),
        );
        $manager->persist(
            Visit::forInvalidShortUrl(new Visitor('shlink-tests-agent', 'https://doma.in/foo', '1.2.3.4', '')),
        );

        $manager->flush();
    }
}

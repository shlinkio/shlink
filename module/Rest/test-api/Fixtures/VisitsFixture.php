<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionObject;
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
            Visit::forValidShortUrl($defShortUrl, new Visitor('cf-facebook', '', '127.0.0.1', '')),
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

        $manager->persist($this->setVisitDate(
            Visit::forBasePath(new Visitor('shlink-tests-agent', 'https://doma.in', '1.2.3.4', '')),
            '2020-01-01',
        ));
        $manager->persist($this->setVisitDate(
            Visit::forRegularNotFound(new Visitor('shlink-tests-agent', 'https://doma.in/foo/bar', '1.2.3.4', '')),
            '2020-02-01',
        ));
        $manager->persist($this->setVisitDate(
            Visit::forInvalidShortUrl(new Visitor('cf-facebook', 'https://doma.in/foo', '1.2.3.4', 'foo.com')),
            '2020-03-01',
        ));

        $manager->flush();
    }

    private function setVisitDate(Visit $visit, string $date): Visit
    {
        $ref = new ReflectionObject($visit);
        $dateProp = $ref->getProperty('date');
        $dateProp->setAccessible(true);
        $dateProp->setValue($visit, Chronos::parse($date));

        return $visit;
    }
}

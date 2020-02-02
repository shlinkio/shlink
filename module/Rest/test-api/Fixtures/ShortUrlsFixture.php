<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use ReflectionObject;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;

class ShortUrlsFixture extends AbstractFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     */
    public function load(ObjectManager $manager): void
    {
        $abcShortUrl = $this->setShortUrlDate(
            new ShortUrl('https://shlink.io', ShortUrlMeta::fromRawData(['customSlug' => 'abc123'])),
            '2018-05-01',
        );
        $manager->persist($abcShortUrl);

        $defShortUrl = $this->setShortUrlDate(new ShortUrl(
            'https://blog.alejandrocelaya.com/2017/12/09/acmailer-7-0-the-most-important-release-in-a-long-time/',
            ShortUrlMeta::fromRawData(['validSince' => Chronos::parse('2020-05-01'), 'customSlug' => 'def456']),
        ), '2019-01-01 00:00:10');
        $manager->persist($defShortUrl);

        $customShortUrl = $this->setShortUrlDate(new ShortUrl(
            'https://shlink.io',
            ShortUrlMeta::fromRawData(['customSlug' => 'custom', 'maxVisits' => 2]),
        ), '2019-01-01 00:00:20');
        $manager->persist($customShortUrl);

        $ghiShortUrl = $this->setShortUrlDate(
            new ShortUrl('https://shlink.io/documentation/', ShortUrlMeta::fromRawData(['customSlug' => 'ghi789'])),
            '2018-05-01',
        );
        $manager->persist($ghiShortUrl);

        $withDomainDuplicatingShortCode = $this->setShortUrlDate(new ShortUrl(
            'https://blog.alejandrocelaya.com/2019/04/27/considerations-to-properly-use-open-source-software-projects/',
            ShortUrlMeta::fromRawData(['domain' => 'example.com', 'customSlug' => 'ghi789']),
        ), '2019-01-01 00:00:30');
        $manager->persist($withDomainDuplicatingShortCode);

        $withDomainAndSlugShortUrl = $this->setShortUrlDate(new ShortUrl(
            'https://google.com',
            ShortUrlMeta::fromRawData(['domain' => 'some-domain.com', 'customSlug' => 'custom-with-domain']),
        ), '2018-10-20');
        $manager->persist($withDomainAndSlugShortUrl);

        $manager->flush();

        $this->addReference('abc123_short_url', $abcShortUrl);
        $this->addReference('def456_short_url', $defShortUrl);
        $this->addReference('ghi789_short_url', $ghiShortUrl);
    }

    private function setShortUrlDate(ShortUrl $shortUrl, string $date): ShortUrl
    {
        $ref = new ReflectionObject($shortUrl);
        $dateProp = $ref->getProperty('dateCreated');
        $dateProp->setAccessible(true);
        $dateProp->setValue($shortUrl, Chronos::parse($date));

        return $shortUrl;
    }
}

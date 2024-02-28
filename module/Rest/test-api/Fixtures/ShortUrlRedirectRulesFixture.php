<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class ShortUrlRedirectRulesFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ShortUrlsFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var ShortUrl $defShortUrl */
        $defShortUrl = $this->getReference('def456_short_url');

        $englishCondition = RedirectCondition::forLanguage('en');
        $manager->persist($englishCondition);

        $fooQueryCondition = RedirectCondition::forQueryParam('foo', 'bar');
        $manager->persist($fooQueryCondition);

        $helloQueryCondition = RedirectCondition::forQueryParam('hello', 'world');
        $manager->persist($helloQueryCondition);

        $androidCondition = RedirectCondition::forDevice(DeviceType::ANDROID);
        $manager->persist($androidCondition);

        $iosCondition = RedirectCondition::forDevice(DeviceType::IOS);
        $manager->persist($iosCondition);

        // Create rules disordered to make sure the order by priority works
        $multipleQueryParamsRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 2,
            longUrl: 'https://example.com/multiple-query-params',
            conditions: new ArrayCollection([$helloQueryCondition, $fooQueryCondition]),
        );
        $manager->persist($multipleQueryParamsRule);

        $englishAndFooQueryRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 1,
            longUrl: 'https://example.com/english-and-foo-query',
            conditions: new ArrayCollection([$englishCondition, $fooQueryCondition]),
        );
        $manager->persist($englishAndFooQueryRule);

        $androidRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 4,
            longUrl: 'https://blog.alejandrocelaya.com/android',
            conditions: new ArrayCollection([$androidCondition]),
        );
        $manager->persist($androidRule);

        $onlyEnglishRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 3,
            longUrl: 'https://example.com/only-english',
            conditions: new ArrayCollection([$englishCondition]),
        );
        $manager->persist($onlyEnglishRule);

        $iosRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 5,
            longUrl: 'https://blog.alejandrocelaya.com/ios',
            conditions: new ArrayCollection([$iosCondition]),
        );
        $manager->persist($iosRule);

        $manager->flush();
    }
}

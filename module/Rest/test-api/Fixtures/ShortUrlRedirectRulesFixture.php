<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
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

        $englishAndFooQueryRule = new ShortUrlRedirectRule(
            $defShortUrl,
            1,
            'https://example.com/english-and-foo-query',
            new ArrayCollection([$englishCondition, $fooQueryCondition]),
        );
        $manager->persist($englishAndFooQueryRule);

        $multipleQueryParamsRule = new ShortUrlRedirectRule(
            $defShortUrl,
            2,
            'https://example.com/multiple-query-params',
            new ArrayCollection([$helloQueryCondition, $fooQueryCondition]),
        );
        $manager->persist($multipleQueryParamsRule);

        $onlyEnglishRule = new ShortUrlRedirectRule(
            $defShortUrl,
            3,
            'https://example.com/only-english',
            new ArrayCollection([$englishCondition]),
        );
        $manager->persist($onlyEnglishRule);

        $manager->flush();
    }
}

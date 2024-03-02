<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectRulesData;
use Shlinkio\Shlink\Core\RedirectRule\Model\Validation\RedirectRulesInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function array_map;

readonly class ShortUrlRedirectRuleService implements ShortUrlRedirectRuleServiceInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @return ShortUrlRedirectRule[]
     */
    public function rulesForShortUrl(ShortUrl $shortUrl): array
    {
        return $this->em->getRepository(ShortUrlRedirectRule::class)->findBy(
            criteria: ['shortUrl' => $shortUrl],
            orderBy: ['priority' => 'ASC'],
        );
    }

    /**
     * @return ShortUrlRedirectRule[]
     */
    public function setRulesForShortUrl(ShortUrl $shortUrl, RedirectRulesData $data): array
    {
        $rules = [];
        foreach ($data->rules as $index => $rule) {
            $rule = new ShortUrlRedirectRule(
                shortUrl: $shortUrl,
                priority: $index + 1,
                longUrl: $rule[RedirectRulesInputFilter::RULE_LONG_URL],
                conditions: new ArrayCollection(array_map(
                    RedirectCondition::fromRawData(...),
                    $rule[RedirectRulesInputFilter::RULE_CONDITIONS],
                )),
            );

            $rules[] = $rule;
        }

        $this->saveRulesForShortUrl($shortUrl, $rules);
        return $rules;
    }

    /**
     * @param ShortUrlRedirectRule[] $rules
     */
    public function saveRulesForShortUrl(ShortUrl $shortUrl, array $rules): void
    {
        $this->em->wrapInTransaction(function () use ($shortUrl, $rules): void {
            // First, delete existing rules for the short URL
            $oldRules = $this->rulesForShortUrl($shortUrl);
            foreach ($oldRules as $oldRule) {
                $oldRule->clearConditions(); // This will trigger the orphan removal of old conditions
                $this->em->remove($oldRule);
            }
            $this->em->flush();

            // Then insert new rules
            foreach ($rules as $rule) {
                $this->em->persist($rule);
            }
        });
    }
}

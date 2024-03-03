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
use function Shlinkio\Shlink\Core\ArrayUtils\map;

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

        $this->doSetRulesForShortUrl($shortUrl, $rules);
        return $rules;
    }

    /**
     * @param ShortUrlRedirectRule[] $rules
     */
    public function saveRulesForShortUrl(ShortUrl $shortUrl, array $rules): void
    {
        $normalizedAndDetachedRules = map($rules, function (ShortUrlRedirectRule $rule, int|string|float $priority) {
            // Make sure all rules and conditions are detached so that the EM considers them new.
            $rule->mapConditions(fn (RedirectCondition $cond) => $this->em->detach($cond));
            $this->em->detach($rule);

            // Normalize priorities so that they are sequential
            return $rule->withPriority(((int) $priority) + 1);
        });

        $this->doSetRulesForShortUrl($shortUrl, $normalizedAndDetachedRules);
    }

    /**
     * @param ShortUrlRedirectRule[] $rules
     */
    public function doSetRulesForShortUrl(ShortUrl $shortUrl, array $rules): void
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

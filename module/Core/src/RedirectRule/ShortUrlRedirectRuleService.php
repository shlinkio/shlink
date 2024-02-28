<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
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
        return $this->em->wrapInTransaction(function () use ($shortUrl, $data): array {
            // First, delete existing rules for the short URL
            $oldRules = $this->rulesForShortUrl($shortUrl);
            foreach ($oldRules as $oldRule) {
                $oldRule->clearConditions(); // This will trigger the orphan removal of old conditions
                $this->em->remove($oldRule);
            }
            $this->em->flush();

            // Then insert new rules
            $rules = [];
            foreach ($data->rules as $rule) {
                $rule = new ShortUrlRedirectRule(
                    shortUrl: $shortUrl,
                    priority: $rule[RedirectRulesInputFilter::RULE_PRIORITY],
                    longUrl: $rule[RedirectRulesInputFilter::RULE_LONG_URL],
                    conditions: new ArrayCollection(array_map(
                        fn (array $conditionData) => $this->createCondition($conditionData),
                        $rule[RedirectRulesInputFilter::RULE_CONDITIONS],
                    )),
                );

                $rules[] = $rule;
                $this->em->persist($rule);
            }

            return $rules;
        });
    }

    private function createCondition(array $rawConditionData): RedirectCondition
    {
        $type = RedirectConditionType::from($rawConditionData[RedirectRulesInputFilter::CONDITION_TYPE]);
        $value = $rawConditionData[RedirectRulesInputFilter::CONDITION_MATCH_VALUE];
        $key = $rawConditionData[RedirectRulesInputFilter::CONDITION_MATCH_KEY];

        return match ($type) {
            RedirectConditionType::DEVICE => RedirectCondition::forDevice(DeviceType::from($value)),
            RedirectConditionType::LANGUAGE => RedirectCondition::forLanguage($value),
            RedirectConditionType::QUERY_PARAM => RedirectCondition::forQueryParam($key, $value),
        };
    }
}

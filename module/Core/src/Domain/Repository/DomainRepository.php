<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Core\Domain\Spec\IsDomain;
use Shlinkio\Shlink\Core\Domain\Spec\IsNotAuthority;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToApiKey;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DomainRepository extends EntitySpecificationRepository implements DomainRepositoryInterface
{
    /**
     * @return Domain[]
     */
    public function findDomainsWithout(?string $excludedAuthority, ?ApiKey $apiKey = null): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->leftJoin(ShortUrl::class, 's', Join::WITH, 's.domain = d')
           ->orderBy('d.authority', 'ASC')
           ->groupBy('d')
           ->having($qb->expr()->gt('COUNT(s.id)', '0'))
           ->orHaving($qb->expr()->isNotNull('d.baseUrlRedirect'))
           ->orHaving($qb->expr()->isNotNull('d.regular404Redirect'))
           ->orHaving($qb->expr()->isNotNull('d.invalidShortUrlRedirect'));

        $specs = $this->determineExtraSpecs($excludedAuthority, $apiKey);
        foreach ($specs as [$alias, $spec]) {
            $this->applySpecification($qb, $spec, $alias);
        }

        return $qb->getQuery()->getResult();
    }

    private function determineExtraSpecs(?string $excludedAuthority, ?ApiKey $apiKey): iterable
    {
        if ($excludedAuthority !== null) {
            yield ['d', new IsNotAuthority($excludedAuthority)];
        }

        // FIXME The $apiKey->spec() method cannot be used here, as it returns a single spec which assumes the
        //       ShortUrl is the root entity. Here, the Domain is the root entity.
        //       Think on a way to centralize the conditional behavior and make $apiKey->spec() more flexible.
        yield from $apiKey?->mapRoles(fn (string $roleName, array $meta) => match ($roleName) {
            Role::DOMAIN_SPECIFIC => ['d', new IsDomain(Role::domainIdFromMeta($meta))],
            Role::AUTHORED_SHORT_URLS => ['s', new BelongsToApiKey($apiKey)],
            default => [null, Spec::andX()],
        }) ?? [];
    }
}

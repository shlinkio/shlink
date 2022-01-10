<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20200323190014 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');
        $this->skipIf($visitLocations->hasColumn('is_empty'));

        $visitLocations->addColumn('is_empty', Types::BOOLEAN, ['default' => false]);
    }

    public function postUp(Schema $schema): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update('visit_locations')
           ->set('is_empty', ':isEmpty')
           ->where($qb->expr()->eq('country_code', ':emptyString'))
           ->andWhere($qb->expr()->eq('country_name', ':emptyString'))
           ->andWhere($qb->expr()->eq('region_name', ':emptyString'))
           ->andWhere($qb->expr()->eq('city_name', ':emptyString'))
           ->andWhere($qb->expr()->eq('timezone', ':emptyString'))
           ->andWhere($qb->expr()->eq('lat', 0))
           ->andWhere($qb->expr()->eq('lon', 0))
           ->setParameter('isEmpty', true)
           ->setParameter('emptyString', '')
           ->executeStatement();
    }

    public function down(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');
        $this->skipIf(!$visitLocations->hasColumn('is_empty'));

        $visitLocations->dropColumn('is_empty');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}

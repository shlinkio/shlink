<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

use function Functional\none;

final class Version20200106215144 extends AbstractMigration
{
    private const COLUMNS = ['latitude', 'longitude'];

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');
        $this->skipIf(none(
            self::COLUMNS,
            fn (string $oldColName) => $visitLocations->hasColumn($oldColName),
        ), 'Old columns do not exist');

        foreach (self::COLUMNS as $colName) {
            $visitLocations->dropColumn($colName);
        }
    }

    /**
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::COLUMNS as $colName) {
            $visitLocations->addColumn($colName, Types::STRING, [
                'notnull' => false,
            ]);
        }
    }
}

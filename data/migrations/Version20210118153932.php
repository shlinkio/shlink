<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210118153932 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Prev migration used to set the length to 256, which made some set-ups crash
        // It has been updated to 255, and this migration ensures whoever managed to run the prev one, gets the value
        // also updated to 255

        $rolesTable = $schema->getTable('api_key_roles');
        $nameColumn = $rolesTable->getColumn('role_name');
        $nameColumn->setLength(255);
    }

    public function down(Schema $schema): void
    {
    }
}

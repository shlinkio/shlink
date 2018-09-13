<?php
declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\Util\IpAddress;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180913205455 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        if ($visits->hasColumn('remote_addr_hash')) {
            return;
        }

        $visits->addColumn('remote_addr_hash', Type::STRING, [
            'notnull' => false,
            'length' => 128,
        ]);
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function postUp(Schema $schema)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id', 'remote_addr', 'visit_location_id')
           ->from('visits');
        $st = $this->connection->executeQuery($qb->getSQL());

        $qb = $this->connection->createQueryBuilder();
        $qb->update('visits', 'v')
           ->set('v.remote_addr_hash', ':hash')
           ->set('v.remote_addr', ':obfuscatedAddr')
           ->where('v.id=:id');

        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $addr = $row['remote_addr'] ?? null;
            if ($addr === null) {
                continue;
            }

            $qb->setParameters([
                'id' => $row['id'],
                'hash' => \hash('sha256', $addr),
                'obfuscatedAddr' => $this->determineAddress((string) $addr, $row),
            ])->execute();
        }
    }

    private function determineAddress(string $addr, array $row): ?string
    {
        // When the visit has already been located, drop the IP address
        if (isset($row['visit_location_id'])) {
            return null;
        }

        if ($addr === IpAddress::LOCALHOST) {
            return $addr;
        }

        try {
            return (string) IpAddress::fromString($addr)->getObfuscatedCopy();
        } catch (WrongIpException $e) {
            return null;
        }
    }

    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $visits->dropColumn('remote_addr_hash');
    }
}

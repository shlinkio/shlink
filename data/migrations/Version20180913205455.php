<?php
declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
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
     */
    public function up(Schema $schema): void
    {
        // Nothing to create
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function postUp(Schema $schema): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id', 'remote_addr')
           ->from('visits');
        $st = $this->connection->executeQuery($qb->getSQL());

        $qb = $this->connection->createQueryBuilder();
        $qb->update('visits', 'v')
           ->set('v.remote_addr', ':obfuscatedAddr')
           ->where('v.id=:id');

        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $addr = $row['remote_addr'] ?? null;
            if ($addr === null) {
                continue;
            }

            $qb->setParameters([
                'id' => $row['id'],
                'obfuscatedAddr' => $this->determineAddress((string) $addr, $row),
            ])->execute();
        }
    }

    private function determineAddress(string $addr, array $row): ?string
    {
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
     */
    public function down(Schema $schema): void
    {
        // Nothing to rollback
    }
}

<?php
declare(strict_types = 1);

namespace Externals\Application\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Externals\Application\Database\SchemaDefinition;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DbCommand
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var SchemaDefinition
     */
    private $schemaDefinition;

    public function __construct(Connection $db, SchemaDefinition $schemaDefinition)
    {
        $this->db = $db;
        $this->schemaDefinition = $schemaDefinition;
    }

    public function __invoke(bool $force, OutputInterface $output)
    {
        $newSchema = new Schema();
        $this->schemaDefinition->define($newSchema);
        $currentSchema = $this->db->getSchemaManager()->createSchema();

        $migrationQueries = $currentSchema->getMigrateToSql($newSchema, $this->db->getDatabasePlatform());

        $this->db->transactional(function () use ($migrationQueries, $force, $output) {
            foreach ($migrationQueries as $query) {
                $output->writeln(sprintf('Running <info>%s</info>', $query));
                if ($force) {
                    $this->db->exec($query);
                }
            }
            if (empty($migrationQueries)) {
                $output->writeln('<info>The database is up to date</info>');
            }
        });

        if (!$force) {
            $output->writeln('<comment>No query was run, use the --force option to run the queries</comment>');
        } else {
            $output->writeln('<comment>Queries were successfully run against the database</comment>');
        }
    }
}

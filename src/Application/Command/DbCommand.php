<?php
declare(strict_types = 1);

namespace Externals\Application\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
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

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function __invoke(bool $force, OutputInterface $output)
    {
        $schemaManager = $this->db->getSchemaManager();
        $newSchema = new Schema();

        // Emails tables
        $emailsTable = $newSchema->createTable('emails');
        $emailsTable->addColumn('id', 'string');
        $emailsTable->addColumn('subject', 'text');
        $emailsTable->addColumn('threadId', 'integer', ['unsigned' => true]);
        $emailsTable->addColumn('date', 'datetime');
        $emailsTable->addColumn('content', 'text');
        $emailsTable->addColumn('originalContent', 'text');
        $emailsTable->addColumn('fromEmail', 'string');
        $emailsTable->addColumn('fromName', 'string', ['notnull' => false]);
        $emailsTable->addColumn('imapId', 'string', ['notnull' => false]);
        $emailsTable->addColumn('inReplyTo', 'string', ['notnull' => false]);
        $emailsTable->setPrimaryKey(['id']);
        $emailsTable->addIndex(['threadId']);

        // Threads table
        $threadsTable = $newSchema->createTable('threads');
        $threadsTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $threadsTable->addColumn('subject', 'text');
        $threadsTable->setPrimaryKey(['id']);

        // Users table
        $threadsTable = $newSchema->createTable('users');
        $threadsTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $threadsTable->addColumn('githubId', 'string');
        $threadsTable->addColumn('name', 'string');
        $threadsTable->setPrimaryKey(['id']);
        $threadsTable->addIndex(['githubId']);

        // Reading status table
        $threadsTable = $newSchema->createTable('user_threads_read');
        $threadsTable->addColumn('userId', 'integer', ['unsigned' => true]);
        $threadsTable->addColumn('threadId', 'integer', ['unsigned' => true]);
        $threadsTable->addColumn('emailsRead', 'integer');
        $threadsTable->setPrimaryKey(['userId', 'threadId']);

        $this->db->transactional(function () use ($schemaManager, $newSchema, $force, $output) {
            $currentSchema = $schemaManager->createSchema();
            $queries = $currentSchema->getMigrateToSql($newSchema, $this->db->getDatabasePlatform());

            foreach ($queries as $query) {
                $output->writeln(sprintf('Running <info>%s</info>', $query));
                if ($force) {
                    $this->db->exec($query);
                }
            }
            if (empty($queries)) {
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

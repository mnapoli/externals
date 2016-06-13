<?php
declare(strict_types = 1);

namespace Externals\Application\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class InitCommand
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function __invoke()
    {
        $schemaManager = $this->db->getSchemaManager();
        $schema = new Schema();

        $this->db->beginTransaction();

        // Drop all existing tables
        $tables = $schemaManager->listTables();
        foreach ($tables as $table) {
            $schemaManager->dropTable($table->getName());
        }

        // Emails tables
        $emailsTable = $schema->createTable('emails');
        $emailsTable->addColumn('id', 'string');
        $emailsTable->addColumn('subject', 'text');
        $emailsTable->addColumn('threadId', 'integer', ['unsigned' => true]);
        $emailsTable->addColumn('date', 'datetime');
        $emailsTable->addColumn('content', 'text');
        $emailsTable->addColumn('originalContent', 'text');
        $emailsTable->setPrimaryKey(['id']);
        $emailsTable->addIndex(['threadId']);

        // Threads table
        $threadsTable = $schema->createTable('threads');
        $threadsTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $threadsTable->addColumn('subject', 'text');
        $threadsTable->addIndex(['subject']);
        $threadsTable->setPrimaryKey(['id']);

        foreach ($schema->toSql($this->db->getDatabasePlatform()) as $query) {
            $this->db->exec($query);
        }

        $this->db->commit();
    }
}

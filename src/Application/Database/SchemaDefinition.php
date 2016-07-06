<?php
declare(strict_types = 1);

namespace Externals\Application\Database;

use Doctrine\DBAL\Schema\Schema;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SchemaDefinition
{
    public function define(Schema $schema)
    {
        // Emails tables
        $emailsTable = $schema->createTable('emails');
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
        $threadsTable = $schema->createTable('threads');
        $threadsTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $threadsTable->addColumn('subject', 'text');
        $threadsTable->setPrimaryKey(['id']);

        // Users table
        $threadsTable = $schema->createTable('users');
        $threadsTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $threadsTable->addColumn('githubId', 'string');
        $threadsTable->addColumn('name', 'string');
        $threadsTable->setPrimaryKey(['id']);
        $threadsTable->addIndex(['githubId']);

        // Thread reading status table
        $threadsTable = $schema->createTable('user_threads_read');
        $threadsTable->addColumn('userId', 'integer', ['unsigned' => true]);
        $threadsTable->addColumn('threadId', 'integer', ['unsigned' => true]);
        $threadsTable->addColumn('emailsRead', 'integer');
        $threadsTable->setPrimaryKey(['userId', 'threadId']);

        // Email reading status table
        $threadsTable = $schema->createTable('user_emails_read');
        $threadsTable->addColumn('userId', 'integer', ['unsigned' => true]);
        $threadsTable->addColumn('emailId', 'string');
        $threadsTable->setPrimaryKey(['userId', 'emailId']);
    }
}

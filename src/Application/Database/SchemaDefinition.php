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
        $emailsTable->addColumn('number', 'integer', ['unsigned' => true]);
        $emailsTable->addColumn('subject', 'text');
        $emailsTable->addColumn('threadId', 'string', ['notnull' => false]);
        // This field can be computed at runtime but storing it allows for efficient (and clearer) SQL queries
        $emailsTable->addColumn('isThreadRoot', 'boolean');
        $emailsTable->addColumn('date', 'datetime');
        $emailsTable->addColumn('fetchDate', 'datetime');
        $emailsTable->addColumn('content', 'text');
        $emailsTable->addColumn('source', 'text');
        $emailsTable->addColumn('fromEmail', 'string', ['notnull' => false]);
        $emailsTable->addColumn('fromName', 'string', ['notnull' => false]);
        $emailsTable->addColumn('inReplyTo', 'string', ['notnull' => false]);
        $emailsTable->setPrimaryKey(['id']);
        $emailsTable->addUniqueIndex(['number'], 'unique_number');
        $emailsTable->addIndex(['threadId'], 'index_threadId'); // No foreign key because the email could not exist
        $emailsTable->addIndex(['isThreadRoot'], 'index_isThreadRoot');

        // Threads tables
        // Is like a materialized view, refreshed after emails are synchronized
        $threadsTable = $schema->createTable('threads');
        $threadsTable->addColumn('emailId', 'string');
        $threadsTable->addColumn('lastUpdate', 'datetime');
        $threadsTable->addColumn('emailCount', 'integer', ['unsigned' => true]);
        $threadsTable->setPrimaryKey(['emailId']);
        $threadsTable->addForeignKeyConstraint($emailsTable, ['emailId'], ['id'], [
            'onDelete' => 'CASCADE',
        ], 'foreign_emailId');
        $threadsTable->addIndex(['lastUpdate'], 'index_lastUpdate');

        // Threads table
        // @deprecated Kept for keeping the old URLs
        $oldThreadsTable = $schema->createTable('threads_old');
        $oldThreadsTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $oldThreadsTable->addColumn('subject', 'text');
        $oldThreadsTable->setPrimaryKey(['id']);

        // Users table
        $usersTable = $schema->createTable('users');
        $usersTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $usersTable->addColumn('githubId', 'string');
        $usersTable->addColumn('name', 'string');
        $usersTable->setPrimaryKey(['id']);
        $usersTable->addIndex(['githubId'], 'index_githubId');

        // Email reading status table
        $readTable = $schema->createTable('user_emails_read');
        $readTable->addColumn('userId', 'integer', ['unsigned' => true]);
        $readTable->addColumn('emailId', 'string');
        $readTable->addColumn('lastReadDate', 'datetime');
        $readTable->setPrimaryKey(['userId', 'emailId']);
        $readTable->addForeignKeyConstraint($usersTable, ['userId'], ['id'], [
            'onDelete' => 'CASCADE',
        ], 'foreign_userId');
        $readTable->addForeignKeyConstraint($emailsTable, ['emailId'], ['id'], [
            'onDelete' => 'CASCADE',
        ], 'foreign_emailId');
    }
}

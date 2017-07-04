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
        $emailsTable->addUniqueIndex(['number']);
        $emailsTable->addIndex(['threadId']); // No foreign key because the email could not exist
        $emailsTable->addIndex(['isThreadRoot']); // No foreign key because the email could not exist

        // Threads table
        // @deprecated Kept for keeping the old URLs
        $threadsTable = $schema->createTable('threads');
        $threadsTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $threadsTable->addColumn('subject', 'text');
        $threadsTable->setPrimaryKey(['id']);

        // Users table
        $usersTable = $schema->createTable('users');
        $usersTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $usersTable->addColumn('githubId', 'string');
        $usersTable->addColumn('name', 'string');
        $usersTable->setPrimaryKey(['id']);
        $usersTable->addIndex(['githubId']);

        // Email reading status table
        $readTable = $schema->createTable('user_emails_read');
        $readTable->addColumn('userId', 'integer', ['unsigned' => true]);
        $readTable->addColumn('emailId', 'string');
        $readTable->addColumn('lastReadDate', 'datetime');
        $readTable->setPrimaryKey(['userId', 'emailId']);
        $readTable->addForeignKeyConstraint($usersTable, ['userId'], ['id'], [
            'onDelete' => 'CASCADE',
        ]);
        $readTable->addForeignKeyConstraint($emailsTable, ['emailId'], ['id'], [
            'onDelete' => 'CASCADE',
        ]);
    }
}

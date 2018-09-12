<?php
declare(strict_types=1);

namespace Externals\Application\Database;

use Doctrine\DBAL\Platforms\MySQL57Platform;

/**
 * Overload the MySQL 5.7 platform to use the utf8mb4 charset when creating tables.
 *
 * That will allow to use full UTF-8 (and store messages containing, for example, emojis).
 *
 * @see https://github.com/doctrine/dbal/pull/851
 */
class CustomMySQLPlatform extends MySQL57Platform
{
    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        // Charset
        if (! isset($options['charset'])) {
            $options['charset'] = 'utf8mb4';
        }

        // Collate
        if (! isset($options['collate'])) {
            $options['collate'] = 'utf8mb4_unicode_ci';
        }

        return parent::_getCreateTableSQL($tableName, $columns, $options);
    }
}

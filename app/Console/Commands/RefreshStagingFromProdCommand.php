<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class RefreshStagingFromProdCommand extends Command
{
    private const SOURCE_DATABASE = 'externals-prod';

    protected $signature = 'staging:refresh-from-prod';
    protected $description = 'Drop the current database and recopy all tables from the prod database (same RDS instance)';

    public function handle(): int
    {
        if (App::isProduction()) {
            $this->error('Refusing to run in the production environment.');

            return self::FAILURE;
        }

        $target = config('database.connections.mysql.database');

        if ($target === self::SOURCE_DATABASE) {
            $this->error('Refusing to run: current database is the prod database.');

            return self::FAILURE;
        }

        $start = microtime(true);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->listTables($target) as $table) {
            DB::statement("DROP TABLE `{$target}`.`{$table}`");
        }

        foreach ($this->listTables(self::SOURCE_DATABASE) as $table) {
            DB::statement("CREATE TABLE `{$target}`.`{$table}` LIKE `" . self::SOURCE_DATABASE . "`.`{$table}`");
            DB::statement("INSERT INTO `{$target}`.`{$table}` SELECT * FROM `" . self::SOURCE_DATABASE . "`.`{$table}`");
            $this->info("Copied {$table}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->comment(sprintf('Staging database refreshed in %.2f seconds', microtime(true) - $start));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function listTables(string $database): array
    {
        $rows = DB::select("SHOW TABLES FROM `{$database}`");

        return array_values(array_map(fn($row) => array_values((array) $row)[0], $rows));
    }
}

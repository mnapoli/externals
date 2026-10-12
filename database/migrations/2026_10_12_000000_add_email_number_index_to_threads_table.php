<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The sitemap lists threads ordered by number with their last update:
     * a covering index avoids scanning and sorting the whole table.
     */
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->index(['emailNumber', 'lastUpdate'], 'index_emailNumber_lastUpdate');
        });
    }

    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex('index_emailNumber_lastUpdate');
        });
    }
};

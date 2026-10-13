<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Community votes on RFCs: +1 or -1, one per user and RFC
        Schema::create('rfc_votes', function (Blueprint $table) {
            $table->unsignedInteger('userId');
            $table->unsignedInteger('rfcId')->index('index_rfc_votes_rfcId');
            $table->tinyInteger('value');
            $table->dateTime('updatedAt');

            $table->primary(['userId', 'rfcId']);
            $table->foreign('userId', 'rfc_votes_foreign_userId')
                ->references('id')->on('users')
                ->cascadeOnDelete();
            $table->foreign('rfcId', 'rfc_votes_foreign_rfcId')
                ->references('id')->on('rfcs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfc_votes');
    }
};

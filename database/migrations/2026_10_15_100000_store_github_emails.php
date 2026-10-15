<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The primary address of the GitHub account. Nothing to backfill: GitHub only gives it
            // at login, so accounts that predate this have none until they log in again.
            $table->string('email')->nullable()->after('name');
            // Not unique: two GitHub accounts can share an address
            $table->index('email', 'users_index_email');
        });

        // Every verified address of the GitHub account, lowercased: the one used on the mailing
        // list is often not the primary one. Shows the GitHub avatar of the author of an email.
        Schema::create('user_verified_emails', function (Blueprint $table) {
            $table->unsignedInteger('userId');
            $table->string('email');

            $table->primary(['userId', 'email']);
            $table->index('email', 'user_verified_emails_index_email');
            $table->foreign('userId', 'user_verified_emails_foreign_userId')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_verified_emails');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_index_email');
            $table->dropColumn('email');
        });
    }
};

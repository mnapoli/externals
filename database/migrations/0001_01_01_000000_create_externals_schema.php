<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Externals.io schema, ported from the v1 Doctrine\DBAL schema definition.
 *
 * Column names use camelCase to preserve compatibility with the existing
 * production data — please do not rename them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('number')->unique('unique_number');
            $table->longText('subject');
            $table->string('threadId')->nullable()->index('index_threadId');
            // Stored even though it could be computed: makes SQL queries simpler and faster
            $table->boolean('isThreadRoot')->index('index_isThreadRoot');
            $table->dateTime('date');
            $table->dateTime('fetchDate');
            $table->longText('content');
            $table->longText('source');
            $table->string('fromEmail')->nullable();
            $table->string('fromName')->nullable();
            $table->string('inReplyTo')->nullable();
        });

        Schema::create('threads', function (Blueprint $table) {
            $table->string('emailId')->primary();
            $table->unsignedInteger('emailNumber');
            $table->dateTime('lastUpdate')->index('index_lastUpdate');
            $table->unsignedInteger('emailCount');
            $table->integer('votes')->default(0);

            $table->foreign('emailId', 'foreign_emailId')
                ->references('id')->on('emails')
                ->cascadeOnDelete();
        });

        // Kept for backwards compatibility with old thread URLs.
        Schema::create('threads_old', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->longText('subject');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('githubId')->index('index_githubId');
            $table->string('name');
        });

        Schema::create('user_emails_read', function (Blueprint $table) {
            $table->unsignedInteger('userId');
            $table->string('emailId');
            $table->dateTime('lastReadDate');

            $table->primary(['userId', 'emailId']);
            $table->foreign('userId', 'foreign_userId')
                ->references('id')->on('users')
                ->cascadeOnDelete();
            $table->foreign('emailId', 'foreign_emailId')
                ->references('id')->on('emails')
                ->cascadeOnDelete();
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->unsignedInteger('userId');
            $table->unsignedInteger('emailNumber');
            $table->integer('value');
            $table->dateTime('updatedAt');

            $table->primary(['userId', 'emailNumber']);
            $table->foreign('userId', 'foreign_userId')
                ->references('id')->on('users')
                ->cascadeOnDelete();
            $table->foreign('emailNumber', 'foreign_emailNumber')
                ->references('number')->on('emails')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::dropIfExists('user_emails_read');
        Schema::dropIfExists('users');
        Schema::dropIfExists('threads_old');
        Schema::dropIfExists('threads');
        Schema::dropIfExists('emails');
    }
};

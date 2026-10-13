<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RFCs imported from https://wiki.php.net/rfc, and the threads that discuss them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfcs', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            // DokuWiki page ID without the "rfc:" namespace, e.g. "pipe-operator-v3"
            $table->string('slug')->unique('unique_slug');
            $table->string('title');
            // Status and position come from the sections of the wiki index page
            $table->string('status')->index('index_status');
            $table->string('indexSubsection')->nullable();
            $table->string('phpVersion')->nullable();
            $table->unsignedInteger('indexPosition');
            // RFCs removed from the index are kept so that links keep working
            $table->boolean('isListed')->default(true);
            // Free text fields from the header of the RFC page
            $table->string('version')->nullable();
            $table->string('date')->nullable();
            $table->text('authors')->nullable();
            $table->string('rawStatus')->nullable();
            $table->string('targetVersion')->nullable();
            $table->text('implementation')->nullable();
            $table->longText('content')->nullable();
            $table->dateTime('fetchedAt')->nullable();
        });

        Schema::create('rfc_polls', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('rfcId')->index('index_rfcId');
            $table->unsignedInteger('position');
            $table->text('question');
            $table->boolean('isClosed');
            $table->string('closesAt')->nullable();
            $table->json('choices');
            $table->json('voters');

            $table->foreign('rfcId', 'rfc_polls_foreign_rfcId')
                ->references('id')->on('rfcs')
                ->cascadeOnDelete();
        });

        // Not a foreign key on either side: links found in emails are stored even
        // before the RFC is imported, and joined on the slug later.
        Schema::create('rfc_threads', function (Blueprint $table) {
            $table->string('rfcSlug');
            // Number of the root email of the thread
            $table->unsignedInteger('threadNumber')->index('index_threadNumber');
            // "email": the root email links to the RFC; "wiki": the RFC links to the thread
            $table->string('source');
            $table->primary(['rfcSlug', 'threadNumber', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfc_threads');
        Schema::dropIfExists('rfc_polls');
        Schema::dropIfExists('rfcs');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Emoji reactions on emails (GitHub's set), several per user and email
        Schema::create('email_reactions', function (Blueprint $table) {
            $table->unsignedInteger('userId');
            $table->unsignedInteger('emailNumber')->index('index_email_reactions_emailNumber');
            $table->string('reaction', 16);
            $table->dateTime('createdAt');

            $table->primary(['userId', 'emailNumber', 'reaction']);
            $table->foreign('userId', 'email_reactions_foreign_userId')
                ->references('id')->on('users')
                ->cascadeOnDelete();
            $table->foreign('emailNumber', 'email_reactions_foreign_emailNumber')
                ->references('number')->on('emails')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_reactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('url', 2000)->nullable();
            $table->string('image_url', 2000)->nullable();
            $table->string('author_name')->nullable();
            $table->timestamp('published_at');
            $table->enum('source', ['guardian', 'news_api', 'ny_times']);
            $table->timestamps();

            $table->unique(['external_id', 'source']);
            $table->index(['source', 'published_at']);
            $table->index('published_at');
            $table->index('title');
            $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};

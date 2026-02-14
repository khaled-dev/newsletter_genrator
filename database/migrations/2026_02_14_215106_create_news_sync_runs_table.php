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
        Schema::create('news_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['guardian', 'news_api', 'ny_times']);
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('articles_fetched')->default(0);
            $table->integer('articles_created')->default(0);
            $table->integer('articles_updated')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source', 'started_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_sync_runs');
    }
};


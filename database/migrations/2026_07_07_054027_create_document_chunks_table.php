<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable the vector extension (the pgvector image ships it,
        // but each database must switch it on once)
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('document_chunks', function (Blueprint $table) { 
            $table->id();
            $table->text('content');           // the chunk's text
            $table->integer('token_count');    // how big it was
            $table->timestamps();
        });

        // Laravel's Blueprint has no vector column type,
        // so we add it with raw SQL. The 1536 here MUST match
        // the embedding model's output — this is the contract
        // your validation cast enforces.
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(1536)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};

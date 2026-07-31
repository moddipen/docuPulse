<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use Illuminate\Support\Str;
use App\Models\DocumentChunk;
#[Signature('docupulse:search {question}')]
#[Description('Command description')]
class SearchChunks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $question = $this->argument('question');
        $embedding = Str::of($question)->toEmbeddings();
        $results = DocumentChunk::nearestTo($embedding)->get();
        foreach ($results as $chunk) {
            $this->info(sprintf(
                'distance %.4f | %s',
                $chunk->distance,
                substr($chunk->content, 0, 60)
            ));
        }
    }
}

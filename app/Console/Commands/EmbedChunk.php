<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\DocumentChunk;

class EmbedChunk extends Command
{
    protected $signature = 'docupulse:embed';

    protected $description = 'Embed one hardcoded chunk and print the numbers';

    public function handle()
    {
        // Section 6 from your contract — the same 205-token
        // clause you counted in the tokenizer on Day 2
        $chunkText = "EXCEPT FOR BREACHES OF SECTION 4, EACH PARTY'S "
            . "INDEMNIFICATION OBLIGATIONS UNDER SECTION 7, OR DAMAGES " 
            . "ARISING FROM A PARTY'S GROSS NEGLIGENCE OR WILLFUL "
            . "MISCONDUCT, EACH PARTY'S TOTAL CUMULATIVE LIABILITY "
            . "ARISING OUT OF OR RELATED TO THIS AGREEMENT SHALL NOT "
            . "EXCEED THE TOTAL FEES PAID OR PAYABLE BY CLIENT TO "
            . "CONSULTANT UNDER THE APPLICABLE SOW IN THE TWELVE (12) "
            . "MONTHS PRECEDING THE EVENT GIVING RISE TO THE CLAIM.";

        // YOUR LINE GOES HERE:
        // Call the embedding model through the SDK.
        // Hint from Day 10's plan: the SDK adds a method to
        // Laravel strings — str($chunk)->?????????()
        // Find the method name in the SDK docs, Embeddings section:
        // https://laravel.com/docs/ai-sdk (search "Embeddings")
        // toEmbeddings() returns a plain array of floats directly
        $numbers = Str::of($chunkText)->toEmbeddings();
        // Print the proof
        $this->info('First 10 numbers:');
        $this->line(implode(', ', array_slice($numbers, 0, 10)));
        $this->info('Total count: ' . count($numbers));
        $chunk = DocumentChunk::create([
            'content'     => $chunkText,        // your Section 6 string
            'token_count' => 205,
            'embedding'   => $numbers,          // the 1536 floats
        ]);

        $this->info('Stored with ID: ' . $chunk->id);
    }
}

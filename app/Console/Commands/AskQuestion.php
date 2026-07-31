<?php

namespace App\Console\Commands;

use App\Ai\Agents\ContractAnalyst;
use App\Models\AnswerCache;
use App\Models\DocumentChunk;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Enums\Lab;
use Illuminate\Support\Str;
use App\Services\UsageLogger;
use App\Services\CacheLogger;
#[Signature('docupulse:ask {question} {--tenant_id=1} {--debug}')]
#[Description('Command description')]
class AskQuestion extends Command
{
    /**
     * Max cosine distance (pgvector <=>, range 0–2) for a cached answer to
     * count as "the same question". Lower = stricter. Tune to taste.
     */
    private const CACHE_THRESHOLD = 0.35;

    /**
     * Execute the console command.
     */
    public function handle(UsageLogger $logger, CacheLogger $cacheLogger)
    {
        $question = $this->argument('question');
        $tenant_id = $this->option('tenant_id');

        // 1. Embed the question (Day 10/13 line — you know it)
        $embedding = Str::of($question)->toEmbeddings();

        // 1b. Semantic cache lookup: nearest previously-answered question.
        $cached = AnswerCache::nearestTo($embedding, $tenant_id)->first();
        if ($cached && $cached->distance <= self::CACHE_THRESHOLD) {
            $this->info((string) $cached->answer);
            $this->line(sprintf('[cache HIT] distance %.4f', $cached->distance));
            return self::SUCCESS;
        }

        // 2. Retrieve top 3 (your Day 13 scope)
        $chunks = DocumentChunk::nearestTo($embedding, $tenant_id)->get();
        
        // 3. Build Part 2 — glue the chunks into one context block
        $context = $chunks->pluck('content')->implode("\n\n---\n\n");
        
        // 4. Assemble Parts 2 + 3 and send to the agent.
        //    The SDK sends instructions() (Part 1) automatically.
        try {
            $response = (new ContractAnalyst((int) $tenant_id))->prompt("CONTEXT:\n" . $context . "\n\nQUESTION: " . $question, provider: [Lab::OpenAI, Lab::Anthropic]);
        } catch (\InvalidArgumentException $e) {
            echo "<pre>";
            print_r($e);exit;
            // $this->fail($e); // permanent — stop now, don't retry
        }        

    
        // 5. Show the answer
        $this->info((string) $response);
        $this->line('[cache MISS] fresh answer from LLM');

        $logger->record($response);
        // 5b. Store this Q+A+embedding so the next similar question hits cache.
        $cacheLogger->record($response, $question, $embedding, $tenant_id);       

        if ($this->option('debug')) {
            $this->line('===== FULL PROMPT SENT TO LLM =====');
            $this->line("INSTRUCTIONS:\n" . (new ContractAnalyst((int) $tenant_id))->instructions());
            $this->line("\n----- USER MESSAGE -----");
            $this->line("CONTEXT:\n" . $context . "\n\nQUESTION: " . $question);
            $this->line('===================================');
        }
    }
}

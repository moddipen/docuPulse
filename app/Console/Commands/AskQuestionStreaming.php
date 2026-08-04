<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Ai\Agents\ContractAnalyst;

use Illuminate\Support\Str;
use App\Models\DocumentChunk;
#[Signature('docupulse:ask-question-streaming {question} {--tenant_id=1} {--debug}')]
#[Description('Command description')]
class AskQuestionStreaming extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $question = $this->argument('question');
        $tenant_id = (int) $this->option('tenant_id');

        // 1. Embed the question (Day 10/13 line — you know it)
        $embedding = Str::of($question)->toEmbeddings();

        // 2. Retrieve top 3 (your Day 13 scope)
        $chunks = DocumentChunk::nearestTo($embedding, $tenant_id)->get();
        
        // 3. Build Part 2 — glue the chunks into one context block
        $context = $chunks->pluck('content')->implode("\n\n---\n\n");
        
        // 4. Assemble Parts 2 + 3 and send to the agent.
        //    The SDK sends instructions() (Part 1) automatically.

        $response = (new ContractAnalyst($tenant_id))->stream(
            "CONTEXT:\n" . $context . "\n\nQUESTION: " . $question
        );

        foreach($response as $re){
            if ($re instanceof \Laravel\Ai\Streaming\Events\TextDelta) {
                $this->output->write($re->delta);   // confirm the property name
            }
        }      
        $this->newLine();          
    }
}

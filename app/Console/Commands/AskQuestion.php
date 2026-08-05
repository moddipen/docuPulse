<?php

namespace App\Console\Commands;

use App\Services\AnswerQuestion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('docupulse:ask {question} {--tenant_id=1}')]
#[Description('Answer a question about a tenant\'s contract')]
class AskQuestion extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AnswerQuestion $answerQuestion)
    {
        $question  = $this->argument('question');
        $tenant_id = (int) $this->option('tenant_id');

        $result = $answerQuestion->handle($question, $tenant_id);

        $this->info($result->answer);
        $this->line($result->cached
            ? sprintf('[cache HIT] distance %.4f', $result->distance)
            : '[cache MISS] fresh answer from LLM');

        return self::SUCCESS;
    }
}

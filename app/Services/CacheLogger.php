<?php

namespace App\Services;
use App\Models\AnswerCache;
use Laravel\Ai\Responses\AgentResponse;

class CacheLogger
{
   public function record(AgentResponse $response, string $question, array $embedding, int $tenant_id): AnswerCache
   {
        return AnswerCache::create([
            'question'  => $question,
            'answer'    => (string) $response,
            'embedding' => $embedding,
            'tenant_id' => $tenant_id,
        ]);
   }
}

<?php

namespace App\Services;

use App\Ai\Agents\ContractAnalyst;
use App\Models\AnswerCache;
use App\Models\DocumentChunk;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;

/**
 * The contract-QA pipeline: given a question and a tenant, produce an answer.
 *
 * This is the single source of truth for the RAG flow — embed, semantic-cache
 * lookup, tenant-scoped retrieval, LLM generation, and usage/cache logging.
 * The console command and the HTTP controller are thin wrappers around it.
 */
class AnswerQuestion
{
    /**
     * Max cosine distance (pgvector <=>, range 0–2) for a cached answer to
     * count as "the same question". Lower = stricter.
     */
    private const CACHE_THRESHOLD = 0.35;

    public function __construct(
        private UsageLogger $logger,
        private CacheLogger $cacheLogger,
    ) {}

    public function handle(string $question, int $tenantId): AnswerResult
    {
        // 1. Embed the question.
        $embedding = Str::of($question)->toEmbeddings();

        // 2. Semantic cache lookup: nearest previously-answered question for this tenant.
        $cached = AnswerCache::nearestTo($embedding, $tenantId)->first();
        if ($cached && $cached->distance <= self::CACHE_THRESHOLD) {
            return new AnswerResult((string) $cached->answer, cached: true, distance: (float) $cached->distance);
        }

        // 3. Retrieve the top matching chunks (tenant-scoped) and build the context block.
        $context = DocumentChunk::nearestTo($embedding, $tenantId)->get()
            ->pluck('content')
            ->implode("\n\n---\n\n");

        // 4. Ask the agent, grounded in the retrieved context. OpenAI with Anthropic failover.
        $response = (new ContractAnalyst($tenantId))->prompt(
            "CONTEXT:\n" . $context . "\n\nQUESTION: " . $question,
            provider: [Lab::OpenAI, Lab::Anthropic],
        );

        // 5. Log usage/cost and store the answer in the semantic cache for next time.
        $this->logger->record($response);
        $this->cacheLogger->record($response, $question, $embedding, $tenantId);

        return new AnswerResult((string) $response, cached: false);
    }
}

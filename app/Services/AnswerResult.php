<?php

namespace App\Services;

/**
 * The outcome of answering a question: the answer text, whether it came
 * from the semantic cache, and (on a cache hit) the cosine distance of the
 * matched question. Lets callers format the result for their own medium
 * (CLI, JSON, …) without knowing how it was produced.
 */
class AnswerResult
{
    public function __construct(
        public readonly string $answer,
        public readonly bool $cached,
        public readonly ?float $distance = null,
    ) {}
}

<?php

namespace App\Models;

use App\Casts\Vector;
use Illuminate\Database\Eloquent\Model;

use App\Models\DocumentChunk;
class AnswerCache extends Model
{
    // Migration named the table 'answer_cache' (not the default 'answer_caches').
    protected $table = 'answer_cache';

    protected $fillable = ['question', 'answer', 'embedding', 'tenant_id'];

    protected $casts = [
        'embedding' => Vector::class,
    ];

    /**
     * Order cached answers by cosine distance to the given question embedding.
     * Mirrors DocumentChunk::scopeNearestTo — returns the builder with a
     * `distance` column so the caller can ->first() and inspect it.
     */
    public function scopeNearestTo($query, array $embedding, int $tenantId, int $limit = 1)
    {
        // pgvector needs the vector as a string literal: '[0.1,0.2,...]'
        $vector = '[' . implode(',', $embedding) . ']';

        // Note: we deliberately do NOT select `embedding`, so the Vector cast
        // won't parse 1536 floats on every lookup. <=> is cosine distance.
        return $query
            ->select('id', 'question', 'answer')
            ->where('tenant_id', $tenantId)
            ->selectRaw('embedding <=> ? AS distance', [$vector])
            ->orderBy('distance', 'asc')
            ->limit($limit);
    }
}

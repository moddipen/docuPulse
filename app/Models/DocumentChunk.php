<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\Vector;
class DocumentChunk extends Model
{
    protected $fillable = ['content', 'token_count', 'embedding','tenant_id'];
    protected $casts = [
        'embedding' => Vector::class,
    ];

    public function scopeNearestTo($query, array $embedding, int $tenantId, int $limit = 3){
        // Postgres needs the vector as a string literal: '[0.1,0.2,...]'
        $vector = '[' . implode(',', $embedding) . ']';

        // Return the builder so the caller can ->get() / ->first().
        // <=> is pgvector's cosine-distance operator.
        return $query
            ->select('id', 'content', 'token_count')
            ->where('tenant_id', $tenantId)
            ->selectRaw('embedding <=> ? AS distance', [$vector])
            ->orderBy('distance', 'asc')
            ->limit($limit);
    }
}

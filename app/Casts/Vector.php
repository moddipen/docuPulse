<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
class Vector implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    // Database → PHP: turn '[0.1,0.2,...]' back into an array
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        // pgvector returns the column as a string like '[0.1,0.2,...]'
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected a vector string from the database, got ' . gettype($value));
        }

        return array_map('floatval', explode(',', trim($value, '[]')));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {   
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('Embedding must be an array, ' . gettype($value) . ' provided.');
        }

        if (count($value) !== 1536) {
            throw new InvalidArgumentException('Embedding must have 1536 dimensions, ' . count($value) . ' provided.');
        }

        return '[' . implode(',', $value) . ']';
    }
}

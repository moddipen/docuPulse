<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    protected $fillable = [
        'model',
        'input_tokens',
        'output_tokens',
        'estimated_cost',
    ];
}

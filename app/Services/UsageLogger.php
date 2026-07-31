<?php

namespace App\Services;

use App\Models\AiLog;
use Laravel\Ai\Responses\AgentResponse;
class UsageLogger
{
    public function record(AgentResponse $response): AiLog
    {
        $model  = $response->meta->model ?? 'unknown';
        $input  = $response->usage->promptTokens;
        $output = $response->usage->completionTokens;

        return AiLog::create([
            'model'          => $model,
            'input_tokens'   => $input,
            'output_tokens'  => $output,
            'estimated_cost' => $this->estimateCost($model, $input, $output),
        ]);
    }

    public function estimateCost(string $model, int $input, int $output): float
    {
        // Index the array directly: model names contain '.', which config()'s
        // dot-notation would misread as nested keys (gpt-5.4 -> gpt-5 -> 4).
        $prices = config('ai.pricing')[$model] ?? ['input' => 0, 'output' => 0];

        return ($input / 1_000_000) * $prices['input']
             + ($output / 1_000_000) * $prices['output'];
    }
}

<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
class SendLegalAlert implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Send an alert to the legal team when a contract has been flagged for review.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        if (! is_string($request['alert']) || trim($request['alert']) === '') {
            throw new InvalidArgumentException("SendLegalAlert expected a non-empty message, got: " . var_export($request['alert'], true));
        }
        Log::warning('Legal alert: ' . $request['alert']);
        return "Legal team alerted.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'alert' => $schema->string()->required(),
        ];
    }
}

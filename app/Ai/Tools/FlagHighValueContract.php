<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use InvalidArgumentException;
use App\Models\ContractFlag;
class FlagHighValueContract implements Tool
{
    /**
     * The tenant this tool acts on. Injected by the app (never by the LLM),
     * so tenant scoping can't be influenced by the model's output.
     */
    public function __construct(private int $tenantId) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Flag a contract for legal review when a dollar amount in the contract exceeds $5,000. Call this whenever you find a contract value above that threshold.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // The model generated this. Pull it out first.
        $amount = $request['amount'];

        // GUARD 1: is it actually a number?
        // The model is SUPPOSED to send a number (the schema says so),
        // but the schema is a request, not a guarantee. It could send
        // "twelve thousand" or null. Check before trusting it.
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException(
                "FlagHighValueContract expected a numeric amount, got: " . var_export($amount, true)
            );
        }

        // GUARD 2: is it positive?
        // A flag for -$5000 or $0 is meaningless. Reject nonsense values.
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                "FlagHighValueContract expected a positive amount, got: {$amount}"
            );
        }

        // Only now, after the input is proven safe, do the real work.
        ContractFlag::create([
            'amount' => $amount,
            'reason' => 'Dollar amount in the contract exceeds $5,000',
            'tenant_id' => $this->tenantId,
        ]);

        return 'Dollar amount in the contract exceeds $5,000';     
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => $schema->number()->min(1)->required(),
        ];
    }
}

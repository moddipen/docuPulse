<?php

namespace Tests\Feature;

use App\Ai\Tools\FlagHighValueContract;
use App\Models\ContractFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class FlagHighValueContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_flags_a_high_value_contract(): void
    {
        $tool = new FlagHighValueContract();

        $result = $tool->handle(new Request(['amount' => 7500.00]));

        // Returns the confirmation string
        $this->assertSame('Dollar amount in the contract exceeds $5,000', (string) $result);

        // Persists a flag row with the given amount and a reason
        $this->assertDatabaseCount('contract_flags', 1);
        $this->assertDatabaseHas('contract_flags', [
            'amount' => 7500.00,
            'reason' => 'Dollar amount in the contract exceeds $5,000',
        ]);
    }
}

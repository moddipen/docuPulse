<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use App\Ai\Tools\FlagHighValueContract;
use App\Ai\Tools\SendLegalAlert;
use Laravel\Ai\Tools\Request;

use Stringable;

use Illuminate\Support\Str;
use App\Models\AnswerCache;
use App\Models\DocumentChunk;
class ContractAnalyst implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * The tenant this agent's tools act on. Passed in by the command.
     */
    public function __construct(private int $tenantId) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return "You are a contract analyst. Answer questions using ONLY the context provided; if the answer is not in the context, say exactly: 'I do not know.' You also have tools available. When you find a contract value that meets a tool's conditions, call that tool as part of your analysis.";
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new FlagHighValueContract($this->tenantId),
            new SendLegalAlert,
        ];
    }
}

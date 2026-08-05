<?php

namespace App\Http\Controllers;

use App\Services\AnswerQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AskController extends Controller
{
    public function ask(Request $request, AnswerQuestion $answerQuestion): JsonResponse
    {
        // Validate the untrusted HTTP input. A failure returns a 422 JSON
        // response automatically, before any AI work happens.
        $validated = $request->validate([
            'question'  => ['required', 'string'],
            'tenant_id' => ['required', 'integer'],
        ]);

        try {
            $result = $answerQuestion->handle($validated['question'], (int) $validated['tenant_id']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'The analysis service is currently unavailable. Please try again.',
            ], 502);
        }

        return response()->json([
            'answer'    => $result->answer,
            'cached'    => $result->cached,
            'distance'  => $result->distance,
            'tenant_id' => (int) $validated['tenant_id'],
        ]);
    }
}

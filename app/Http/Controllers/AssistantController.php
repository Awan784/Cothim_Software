<?php

namespace App\Http\Controllers;

use App\Services\AccountingAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function chat(Request $request, AccountingAssistantService $assistant): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
        ]);

        $result = $assistant->chat(
            $request->user(),
            $data['message'],
            $data['history'] ?? [],
        );

        return response()->json($result);
    }

    public function confirm(Request $request, AccountingAssistantService $assistant): JsonResponse
    {
        return response()->json($assistant->confirm($request->user()));
    }

    public function cancel(Request $request, AccountingAssistantService $assistant): JsonResponse
    {
        return response()->json($assistant->cancel());
    }
}

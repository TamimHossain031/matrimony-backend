<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $verifications = $request->user()->verifications()->latest()->get();

        return response()->json(['data' => $verifications]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'between:1,4'],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('verification-documents/'.$request->user()->id, 'local');
        }

        $verification = $request->user()->verifications()->create([
            'level' => $validated['level'],
            'status' => 'pending',
            'document_path' => $documentPath,
        ]);

        return response()->json([
            'message' => 'Verification request submitted for review.',
            'data' => $verification,
        ], 201);
    }
}

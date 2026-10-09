<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Support\ContactInfoDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Messaging (blueprint §10). Available only after mutual interest; access is
 * enforced by participant check on every action.
 */
class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn (Conversation $c) => [
                'id' => $c->id,
                'other_user_id' => $c->otherParticipant($userId),
                'is_closed' => $c->is_closed,
                'last_message_at' => $c->last_message_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $conversations]);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $page = $conversation->messages()->latest()->cursorPaginate(30);

        // Mark the other party's messages as read.
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => collect($page->items())->map(fn (Message $m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'body' => $m->body,
                'was_masked' => $m->was_masked,
                'read' => $m->read_at !== null,
                'created_at' => $m->created_at?->toIso8601String(),
            ]),
            'next_cursor' => $page->nextCursor()?->encode(),
            'has_more' => $page->hasMorePages(),
        ]);
    }

    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        abort_if($conversation->is_closed, 422, 'This conversation is closed.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        // Server-side contact-info masking before the message persists (§10).
        $result = ContactInfoDetector::mask($validated['body']);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $result['body'],
            'was_masked' => $result['masked'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'was_masked' => $message->was_masked,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->hasParticipant($request->user()->id), 403, 'This action is unauthorized.');
    }
}

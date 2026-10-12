<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GreetingCardPublicResource;
use App\Models\GreetingCard;
use App\Models\GreetingCardEvent;
use App\Models\GreetingCardReport;
use App\Models\GreetingTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GreetingCardController extends Controller
{
    /**
     * Create and publish a greeting card.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'exists:greeting_templates,id'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'sender_name' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2500'],
            'theme_settings' => ['nullable', 'array'],
            'privacy' => ['nullable', 'in:public_link,protected'],
        ]);

        $template = GreetingTemplate::findOrFail($validated['template_id']);

        // Generate unguessable, collision-free public_id (e.g. 12-char alphanumeric)
        do {
            $publicId = Str::lower(Str::random(12));
        } while (GreetingCard::where('public_id', $publicId)->exists());

        $editToken = Str::random(32);
        $userId = $request->user('sanctum')?->id;

        // Sanitize string inputs to prevent script injection
        $recipientName = $this->sanitizeText($validated['recipient_name']);
        $senderName = isset($validated['sender_name']) ? $this->sanitizeText($validated['sender_name']) : null;
        $title = isset($validated['title']) ? $this->sanitizeText($validated['title']) : null;
        $message = $this->sanitizeText($validated['message']);

        /** @var GreetingCard $card */
        $card = DB::transaction(function () use ($validated, $template, $publicId, $editToken, $userId, $recipientName, $senderName, $title, $message) {
            $createdCard = GreetingCard::create([
                'public_id' => $publicId,
                'edit_token' => $editToken,
                'user_id' => $userId,
                'template_id' => $template->id,
                'occasion' => $template->occasion,
                'recipient_name' => $recipientName,
                'sender_name' => $senderName,
                'title' => $title,
                'message' => $message,
                'theme_settings' => $validated['theme_settings'] ?? null,
                'status' => 'published',
                'privacy' => $validated['privacy'] ?? 'public_link',
                'views_count' => 0,
                'shares_count' => 0,
                'reactions_count' => 0,
                'published_at' => now(),
            ]);

            GreetingCardEvent::create([
                'card_id' => $createdCard->id,
                'event_type' => 'created',
                'created_at' => now(),
            ]);

            GreetingCardEvent::create([
                'card_id' => $createdCard->id,
                'event_type' => 'published',
                'created_at' => now(),
            ]);

            return $createdCard;
        });

        $card->load('template');

        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $shareUrl = "{$frontendUrl}/{$card->public_id}";

        return response()->json([
            'message' => 'Greeting card created successfully.',
            'data' => new GreetingCardPublicResource($card),
            'edit_token' => $editToken,
            'public_id' => $card->public_id,
            'share_url' => $shareUrl,
        ], 201);
    }

    /**
     * Public card viewing endpoint.
     */
    public function showPublic(Request $request, string $publicId): JsonResponse
    {
        /** @var GreetingCard|null $card */
        $card = GreetingCard::query()
            ->with('template')
            ->where('public_id', $publicId)
            ->first();

        if (! $card) {
            return response()->json([
                'message' => 'Greeting card not found.',
                'code' => 'CARD_NOT_FOUND',
            ], 404);
        }

        if ($card->status === 'disabled') {
            return response()->json([
                'message' => 'This greeting card is no longer available.',
                'code' => 'CARD_DISABLED',
            ], 410);
        }

        if ($card->status === 'deleted') {
            return response()->json([
                'message' => 'This greeting card has been removed.',
                'code' => 'CARD_DELETED',
            ], 404);
        }

        if ($card->status !== 'published') {
            return response()->json([
                'message' => 'This greeting card is not currently available.',
                'code' => 'CARD_UNPUBLISHED',
            ], 404);
        }

        // Increment view count asynchronously/cleanly
        $card->increment('views_count');

        // Log opened event
        $referrer = $request->header('referer', '');
        $category = 'direct';
        if (str_contains($referrer, 'whatsapp')) {
            $category = 'whatsapp';
        } elseif (str_contains($referrer, 'facebook') || str_contains($referrer, 'fb')) {
            $category = 'facebook';
        } elseif (str_contains($referrer, 't.me') || str_contains($referrer, 'telegram')) {
            $category = 'telegram';
        } elseif (str_contains($referrer, 'twitter') || str_contains($referrer, 't.co')) {
            $category = 'twitter';
        }

        GreetingCardEvent::create([
            'card_id' => $card->id,
            'event_type' => 'opened',
            'session_id' => $request->header('X-Session-ID'),
            'referrer_category' => $category,
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => new GreetingCardPublicResource($card),
        ]);
    }

    /**
     * Record engagement events (share_clicked, copy_link, create_another_clicked, reaction_clicked).
     */
    public function recordEvent(Request $request, string $publicId): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:share_clicked,copy_link,create_another_clicked,reaction_clicked'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'referrer_category' => ['nullable', 'string', 'max:50'],
        ]);

        /** @var GreetingCard|null $card */
        $card = GreetingCard::where('public_id', $publicId)->first();

        if (! $card) {
            return response()->json(['message' => 'Card not found.'], 404);
        }

        $eventType = $validated['event_type'];

        if (in_array($eventType, ['share_clicked', 'copy_link'], true)) {
            $card->increment('shares_count');
        } elseif ($eventType === 'reaction_clicked') {
            $card->increment('reactions_count');
        }

        GreetingCardEvent::create([
            'card_id' => $card->id,
            'event_type' => $eventType,
            'session_id' => $validated['session_id'] ?? $request->header('X-Session-ID'),
            'referrer_category' => $validated['referrer_category'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'shares_count' => $card->fresh()->shares_count,
            'reactions_count' => $card->fresh()->reactions_count,
            'views_count' => $card->fresh()->views_count,
        ]);
    }

    /**
     * Report a card for inappropriate content or abuse.
     */
    public function report(Request $request, string $publicId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var GreetingCard|null $card */
        $card = GreetingCard::where('public_id', $publicId)->first();

        if (! $card) {
            return response()->json(['message' => 'Card not found.'], 404);
        }

        GreetingCardReport::create([
            'card_id' => $card->id,
            'reason' => trim(strip_tags($validated['reason'])),
            'details' => isset($validated['details']) ? trim(strip_tags($validated['details'])) : null,
            'status' => 'pending',
            'reporter_ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return response()->json([
            'message' => 'Thank you for reporting. Our moderation team will review this card.',
        ], 201);
    }

    /**
     * Update card personalization (requires edit token or owner user).
     */
    public function update(Request $request, string $publicId): JsonResponse
    {
        /** @var GreetingCard|null $card */
        $card = GreetingCard::with('template')->where('public_id', $publicId)->first();

        if (! $card) {
            return response()->json(['message' => 'Card not found.'], 404);
        }

        $editToken = $request->header('X-Edit-Token') ?: $request->input('edit_token');
        $user = $request->user('sanctum');

        if (! $card->isOwnedBy($user, $editToken)) {
            return response()->json([
                'message' => 'Unauthorized to edit this greeting card.',
            ], 403);
        }

        $validated = $request->validate([
            'recipient_name' => ['sometimes', 'required', 'string', 'max:100'],
            'sender_name' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:150'],
            'message' => ['sometimes', 'required', 'string', 'max:2500'],
            'theme_settings' => ['nullable', 'array'],
        ]);

        if (isset($validated['recipient_name'])) {
            $card->recipient_name = $this->sanitizeText($validated['recipient_name']);
        }
        if (array_key_exists('sender_name', $validated)) {
            $card->sender_name = $this->sanitizeText($validated['sender_name']);
        }
        if (array_key_exists('title', $validated)) {
            $card->title = $this->sanitizeText($validated['title']);
        }
        if (isset($validated['message'])) {
            $card->message = $this->sanitizeText($validated['message']);
        }
        if (array_key_exists('theme_settings', $validated)) {
            $card->theme_settings = $validated['theme_settings'];
        }

        $card->save();

        return response()->json([
            'message' => 'Greeting card updated successfully.',
            'data' => new GreetingCardPublicResource($card),
        ]);
    }

    /**
     * Delete/remove card (requires edit token or owner user).
     */
    public function destroy(Request $request, string $publicId): JsonResponse
    {
        /** @var GreetingCard|null $card */
        $card = GreetingCard::where('public_id', $publicId)->first();

        if (! $card) {
            return response()->json(['message' => 'Card not found.'], 404);
        }

        $editToken = $request->header('X-Edit-Token') ?: $request->input('edit_token');
        $user = $request->user('sanctum');

        if (! $card->isOwnedBy($user, $editToken)) {
            return response()->json([
                'message' => 'Unauthorized to delete this greeting card.',
            ], 403);
        }

        $card->update(['status' => 'deleted']);

        return response()->json([
            'message' => 'Greeting card deleted successfully.',
        ]);
    }

    private function sanitizeText(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Strip script, style, and iframe blocks including inner content
        $cleaned = preg_replace('/<(script|style|iframe|object|embed)\b[^>]*>(.*?)<\/\1>/is', '', $input);
        // Strip any remaining HTML tags
        $cleaned = strip_tags((string) $cleaned);

        return trim($cleaned);
    }
}

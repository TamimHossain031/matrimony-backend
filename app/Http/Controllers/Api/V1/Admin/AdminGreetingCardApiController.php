<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GreetingCardPublicResource;
use App\Models\GreetingCard;
use App\Models\GreetingCardReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGreetingCardApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GreetingCard::with('template')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('occasion')) {
            $query->where('occasion', $request->query('occasion'));
        }

        $cards = $query->paginate(20);

        return response()->json([
            'data' => GreetingCardPublicResource::collection($cards),
            'meta' => [
                'current_page' => $cards->currentPage(),
                'last_page' => $cards->lastPage(),
                'total' => $cards->total(),
            ],
        ]);
    }

    public function updateStatus(Request $request, GreetingCard $card): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:published,disabled,deleted'],
        ]);

        $card->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Greeting card status updated.',
            'data' => new GreetingCardPublicResource($card->fresh()->load('template')),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $reports = GreetingCardReport::with('card.template')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function updateReportStatus(Request $request, GreetingCardReport $report): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewed,dismissed,actioned'],
        ]);

        $report->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Report status updated.',
            'data' => $report->fresh(),
        ]);
    }
}

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

    public function templates(Request $request): JsonResponse
    {
        $query = GreetingTemplate::query()->orderBy('sort_order')->orderBy('id');

        if ($request->filled('occasion')) {
            $query->where('occasion', $request->query('occasion'));
        }

        $templates = $query->paginate(20);

        return response()->json([
            'data' => \App\Http\Resources\GreetingTemplateResource::collection($templates),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'unique:greeting_templates,slug'],
            'occasion' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:5120'],
            'design_config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $slug = !empty($validated['slug']) ? \Illuminate\Support\Str::slug($validated['slug']) : \Illuminate\Support\Str::slug($validated['name']);
        $original = $slug;
        $c = 1;
        while (GreetingTemplate::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$c}";
            $c++;
        }

        $thumbnailPath = null;
        if ($request->hasFile('image')) {
            $thumbnailPath = $request->file('image')->store('greeting-templates', 'public');
        }

        $template = GreetingTemplate::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'occasion' => $validated['occasion'],
            'description' => $validated['description'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'design_config' => $validated['design_config'] ?? [],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return response()->json([
            'message' => 'Greeting template created successfully.',
            'data' => new \App\Http\Resources\GreetingTemplateResource($template),
        ], 201);
    }

    public function updateTemplate(Request $request, GreetingTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'slug' => ['sometimes', 'string', 'max:150', \Illuminate\Validation\Rule::unique('greeting_templates', 'slug')->ignore($template->id)],
            'occasion' => ['sometimes', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'design_config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $thumbnailPath = $template->thumbnail_path;

        if ($request->boolean('remove_image')) {
            if ($thumbnailPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbnailPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($thumbnailPath);
            }
            $thumbnailPath = null;
        }

        if ($request->hasFile('image')) {
            if ($thumbnailPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbnailPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($thumbnailPath);
            }
            $thumbnailPath = $request->file('image')->store('greeting-templates', 'public');
        }

        $template->update(array_filter([
            'name' => $validated['name'] ?? $template->name,
            'slug' => !empty($validated['slug']) ? \Illuminate\Support\Str::slug($validated['slug']) : $template->slug,
            'occasion' => $validated['occasion'] ?? $template->occasion,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $template->description,
            'thumbnail_path' => $thumbnailPath,
            'design_config' => array_key_exists('design_config', $validated) ? $validated['design_config'] : $template->design_config,
            'is_active' => array_key_exists('is_active', $validated) ? (bool)$validated['is_active'] : $template->is_active,
            'sort_order' => array_key_exists('sort_order', $validated) ? (int)$validated['sort_order'] : $template->sort_order,
        ], fn ($val) => $val !== null));

        return response()->json([
            'message' => 'Greeting template updated successfully.',
            'data' => new \App\Http\Resources\GreetingTemplateResource($template->fresh()),
        ]);
    }

    public function destroyTemplate(GreetingTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json([
            'message' => 'Greeting template deleted successfully.',
        ]);
    }
}

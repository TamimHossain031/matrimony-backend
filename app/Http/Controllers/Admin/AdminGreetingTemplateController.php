<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GreetingCard;
use App\Models\GreetingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminGreetingTemplateController extends Controller
{
    /**
     * Occasions supported by the platform.
     */
    public static array $occasions = [
        'wedding' => 'Wedding & Nikkah',
        'anniversary' => 'Anniversary',
        'birthday' => 'Birthday',
        'eid' => 'Eid & Festive',
        'congratulations' => 'Congratulations',
        'thankyou' => 'Thank You',
        'love' => 'Love & Romance',
    ];

    public function index(Request $request): View
    {
        $query = GreetingTemplate::withCount('cards')->orderBy('sort_order')->orderBy('id');

        if ($request->filled('occasion') && $request->input('occasion') !== 'all') {
            $query->where('occasion', $request->input('occasion'));
        }

        if ($request->filled('status')) {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('slug', 'like', $search);
            });
        }

        $templates = $query->paginate(12)->withQueryString();

        $stats = [
            'total_templates' => GreetingTemplate::count(),
            'active_templates' => GreetingTemplate::where('is_active', true)->count(),
            'inactive_templates' => GreetingTemplate::where('is_active', false)->count(),
            'total_cards' => GreetingCard::count(),
            'published_cards' => GreetingCard::where('status', 'published')->count(),
        ];

        return view('admin.greetings.templates.index', [
            'templates' => $templates,
            'stats' => $stats,
            'occasions' => self::$occasions,
            'currentOccasion' => $request->input('occasion', 'all'),
            'currentStatus' => $request->input('status', 'all'),
            'search' => $request->input('search', ''),
        ]);
    }

    public function create(): View
    {
        return view('admin.greetings.templates.create', [
            'occasions' => self::$occasions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'unique:greeting_templates,slug'],
            'occasion' => ['required', 'string', Rule::in(array_keys(self::$occasions))],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:5120'],
            'badge' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'in:Sparkles,Heart,Award'],
            'primaryColor' => ['nullable', 'string', 'max:30'],
            'accentColor' => ['nullable', 'string', 'max:30'],
            'bgGradient' => ['nullable', 'string', 'max:255'],
            'cardBg' => ['nullable', 'string', 'max:30'],
            'textColor' => ['nullable', 'string', 'max:30'],
            'borderStyle' => ['nullable', 'string', 'in:double,ornate,floral,arabesque,solid'],
            'fontFamily' => ['nullable', 'string', 'in:serif,cinzel,cinzel-dec,cormorant,calligraphy,script,alex-brush,handwriting,montserrat,sans,traditional'],
            'particleType' => ['nullable', 'string', 'in:golden_stars,rose_petals,sparkles,confetti,floating_hearts,none'],
            'sealColor' => ['nullable', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Check uniqueness and append suffix if collision occurs
        $originalSlug = $slug;
        $counter = 1;
        while (GreetingTemplate::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $thumbnailPath = null;
        if ($request->hasFile('image')) {
            $thumbnailPath = $request->file('image')->store('greeting-templates', 'public');
        }

        $primaryColor = !empty($validated['primaryColor']) ? $validated['primaryColor'] : '#c29b38';
        $accentColor = !empty($validated['accentColor']) ? $validated['accentColor'] : '#e5c06e';

        $designConfig = [
            'themeId' => $slug,
            'primaryColor' => $primaryColor,
            'accentColor' => $accentColor,
            'bgGradient' => !empty($validated['bgGradient']) ? $validated['bgGradient'] : 'linear-gradient(135deg, #1f1b14 0%, #2e2617 50%, #16120d 100%)',
            'cardBg' => !empty($validated['cardBg']) ? $validated['cardBg'] : '#fcfaf5',
            'textColor' => !empty($validated['textColor']) ? $validated['textColor'] : '#2a241b',
            'borderStyle' => !empty($validated['borderStyle']) ? $validated['borderStyle'] : 'double',
            'borderColor' => $accentColor,
            'fontFamily' => !empty($validated['fontFamily']) ? $validated['fontFamily'] : 'serif',
            'particleType' => !empty($validated['particleType']) ? $validated['particleType'] : 'golden_stars',
            'sealColor' => !empty($validated['sealColor']) ? $validated['sealColor'] : $primaryColor,
            'badge' => !empty($validated['badge']) ? $validated['badge'] : '✨ Special Greeting',
            'icon' => !empty($validated['icon']) ? $validated['icon'] : 'Sparkles',
        ];

        $template = GreetingTemplate::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'occasion' => $validated['occasion'],
            'description' => $validated['description'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'design_config' => $designConfig,
            'is_active' => $request->has('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'greeting_template.created',
            subject: $template,
            context: [
                'name' => $template->name,
                'slug' => $template->slug,
                'occasion' => $template->occasion,
                'has_image' => !empty($thumbnailPath),
            ]
        );

        return redirect()->route('admin.greeting-templates.index')
            ->with('success', "Greeting card design '{$template->name}' created successfully!");
    }

    public function edit(GreetingTemplate $template): View
    {
        return view('admin.greetings.templates.edit', [
            'template' => $template,
            'occasions' => self::$occasions,
        ]);
    }

    public function update(Request $request, GreetingTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', Rule::unique('greeting_templates', 'slug')->ignore($template->id)],
            'occasion' => ['required', 'string', Rule::in(array_keys(self::$occasions))],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'badge' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'in:Sparkles,Heart,Award'],
            'primaryColor' => ['nullable', 'string', 'max:30'],
            'accentColor' => ['nullable', 'string', 'max:30'],
            'bgGradient' => ['nullable', 'string', 'max:255'],
            'cardBg' => ['nullable', 'string', 'max:30'],
            'textColor' => ['nullable', 'string', 'max:30'],
            'borderStyle' => ['nullable', 'string', 'in:double,ornate,floral,arabesque,solid'],
            'fontFamily' => ['nullable', 'string', 'in:serif,cinzel,cinzel-dec,cormorant,calligraphy,script,alex-brush,handwriting,montserrat,sans,traditional'],
            'particleType' => ['nullable', 'string', 'in:golden_stars,rose_petals,sparkles,confetti,floating_hearts,none'],
            'sealColor' => ['nullable', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['slug']);

        $thumbnailPath = $template->thumbnail_path;

        if ($request->boolean('remove_image')) {
            if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            $thumbnailPath = null;
        }

        if ($request->hasFile('image')) {
            if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            $thumbnailPath = $request->file('image')->store('greeting-templates', 'public');
        }

        $primaryColor = !empty($validated['primaryColor']) ? $validated['primaryColor'] : ($currentConfig['primaryColor'] ?? '#c29b38');
        $accentColor = !empty($validated['accentColor']) ? $validated['accentColor'] : ($currentConfig['accentColor'] ?? '#e5c06e');

        $currentConfig = $template->design_config ?? [];
        $designConfig = array_merge($currentConfig, [
            'themeId' => $slug,
            'primaryColor' => $primaryColor,
            'accentColor' => $accentColor,
            'bgGradient' => !empty($validated['bgGradient']) ? $validated['bgGradient'] : ($currentConfig['bgGradient'] ?? 'linear-gradient(135deg, #1f1b14 0%, #2e2617 50%, #16120d 100%)'),
            'cardBg' => !empty($validated['cardBg']) ? $validated['cardBg'] : ($currentConfig['cardBg'] ?? '#fcfaf5'),
            'textColor' => !empty($validated['textColor']) ? $validated['textColor'] : ($currentConfig['textColor'] ?? '#2a241b'),
            'borderStyle' => !empty($validated['borderStyle']) ? $validated['borderStyle'] : ($currentConfig['borderStyle'] ?? 'double'),
            'borderColor' => $accentColor,
            'fontFamily' => !empty($validated['fontFamily']) ? $validated['fontFamily'] : ($currentConfig['fontFamily'] ?? 'serif'),
            'particleType' => !empty($validated['particleType']) ? $validated['particleType'] : ($currentConfig['particleType'] ?? 'golden_stars'),
            'sealColor' => !empty($validated['sealColor']) ? $validated['sealColor'] : ($currentConfig['sealColor'] ?? $primaryColor),
            'badge' => !empty($validated['badge']) ? $validated['badge'] : ($currentConfig['badge'] ?? '✨ Special Greeting'),
            'icon' => !empty($validated['icon']) ? $validated['icon'] : ($currentConfig['icon'] ?? 'Sparkles'),
        ]);

        $template->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'occasion' => $validated['occasion'],
            'description' => $validated['description'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'design_config' => $designConfig,
            'is_active' => $request->has('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'greeting_template.updated',
            subject: $template,
            context: [
                'name' => $template->name,
                'slug' => $template->slug,
                'has_image' => !empty($thumbnailPath),
            ]
        );

        return redirect()->route('admin.greeting-templates.index')
            ->with('success', "Greeting card design '{$template->name}' updated successfully!");
    }

    public function destroy(GreetingTemplate $template): RedirectResponse
    {
        $cardsCount = $template->cards()->count();
        $name = $template->name;

        // If template has generated cards, delete cards or keep them?
        // Migration has cascadeOnDelete, but we can also log it
        $template->delete();

        AuditLog::record(
            actor: auth()->user(),
            action: 'greeting_template.deleted',
            subject: $template,
            context: [
                'deleted_name' => $name,
                'cascaded_cards' => $cardsCount,
            ]
        );

        return redirect()->route('admin.greeting-templates.index')
            ->with('success', "Greeting card design '{$name}' was deleted.");
    }

    public function toggleActive(GreetingTemplate $template): RedirectResponse
    {
        $template->is_active = ! $template->is_active;
        $template->save();

        AuditLog::record(
            actor: auth()->user(),
            action: 'greeting_template.toggled_active',
            subject: $template,
            context: [
                'is_active' => $template->is_active,
            ]
        );

        $statusStr = $template->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Template '{$template->name}' is now {$statusStr}.");
    }

    public function uploadImage(Request $request, GreetingTemplate $template): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:5120'],
        ]);

        if ($template->thumbnail_path && Storage::disk('public')->exists($template->thumbnail_path)) {
            Storage::disk('public')->delete($template->thumbnail_path);
        }

        $path = $request->file('image')->store('greeting-templates', 'public');
        $template->update(['thumbnail_path' => $path]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'greeting_template.image_uploaded',
            subject: $template,
            context: ['path' => $path]
        );

        return back()->with('success', "Image updated for '{$template->name}'.");
    }

    public function cardsIndex(Request $request): View
    {
        $query = GreetingCard::with('template', 'user')->latest();

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('occasion') && $request->input('occasion') !== 'all') {
            $query->where('occasion', $request->input('occasion'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', $search)
                  ->orWhere('sender_name', 'like', $search)
                  ->orWhere('title', 'like', $search)
                  ->orWhere('public_id', 'like', $search);
            });
        }

        $cards = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => GreetingCard::count(),
            'published' => GreetingCard::where('status', 'published')->count(),
            'disabled' => GreetingCard::where('status', 'disabled')->count(),
            'views' => GreetingCard::sum('views_count'),
            'shares' => GreetingCard::sum('shares_count'),
            'reactions' => GreetingCard::sum('reactions_count'),
        ];

        return view('admin.greetings.cards.index', [
            'cards' => $cards,
            'stats' => $stats,
            'occasions' => self::$occasions,
            'currentOccasion' => $request->input('occasion', 'all'),
            'currentStatus' => $request->input('status', 'all'),
            'search' => $request->input('search', ''),
        ]);
    }

    public function updateCardStatus(Request $request, GreetingCard $card): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:published,disabled'],
        ]);

        $card->update(['status' => $validated['status']]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'greeting_card.status_updated',
            subject: $card,
            context: ['status' => $validated['status']]
        );

        return back()->with('success', "Greeting card for '{$card->recipient_name}' marked as {$validated['status']}.");
    }

    public function destroyCard(GreetingCard $card): RedirectResponse
    {
        $recipient = $card->recipient_name;
        $card->delete();

        AuditLog::record(
            actor: auth()->user(),
            action: 'greeting_card.deleted',
            subject: $card,
            context: ['recipient' => $recipient]
        );

        return back()->with('success', "Greeting card for '{$recipient}' has been removed.");
    }
}

@extends('admin.layouts.admin')

@section('title', 'Greeting Card Designs')
@section('page_title', 'Greeting Card Designs & Templates')

@section('content')
<div class="space-y-6">
    <!-- Top Sub-Tabs & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
            <a href="{{ route('admin.greeting-templates.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-rose-600 text-white shadow-xs">
                Designs & Templates ({{ $stats['total_templates'] }})
            </a>
            <a href="{{ route('admin.greeting-cards.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                User Sent Cards ({{ $stats['total_cards'] }})
            </a>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.greeting-templates.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Upload New Card Design</span>
            </a>
        </div>
    </div>

    <!-- Quick Platform Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold text-slate-500">Total Designs</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total_templates'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold text-slate-500">Active in Studio</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['active_templates'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold text-slate-500">Inactive Designs</div>
            <div class="text-2xl font-black text-slate-400 mt-1">{{ $stats['inactive_templates'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold text-slate-500">Total Cards Created</div>
            <div class="text-2xl font-black text-rose-600 mt-1">{{ $stats['total_cards'] }}</div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <!-- Occasion Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
            <a href="{{ route('admin.greeting-templates.index', ['occasion' => 'all', 'status' => $currentStatus, 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all shrink-0 {{ $currentOccasion === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                ✨ All Occasions
            </a>
            @foreach($occasions as $key => $label)
                <a href="{{ route('admin.greeting-templates.index', ['occasion' => $key, 'status' => $currentStatus, 'search' => $search]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all shrink-0 {{ $currentOccasion === $key ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- Search & Status Select Form -->
        <form action="{{ route('admin.greeting-templates.index') }}" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100">
            <input type="hidden" name="occasion" value="{{ $currentOccasion }}">

            <div class="relative w-full sm:w-72">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search designs by title or slug..."
                       class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 bg-slate-50">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 py-1.5 px-3 bg-slate-50 font-medium text-slate-700">
                    <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>

                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($templates as $template)
            @php
                $cfg = $template->design_config ?? [];
                $bgGradient = $cfg['bgGradient'] ?? 'linear-gradient(135deg, #1f1b14 0%, #16120d 100%)';
                $primaryColor = $cfg['primaryColor'] ?? '#c29b38';
                $accentColor = $cfg['accentColor'] ?? '#e5c06e';
                $cardBg = $cfg['cardBg'] ?? '#fcfaf5';
                $badge = $cfg['badge'] ?? '✨ Special';
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col group">
                <!-- Card Image / Preview Cover -->
                <div class="relative h-44 w-full overflow-hidden flex items-center justify-center" style="background: {{ $bgGradient }};">
                    @if($template->thumbnail_url)
                        <img src="{{ $template->thumbnail_url }}"
                             alt="{{ $template->name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    @else
                        <!-- Elegant typography & foil badge placeholder when no image is uploaded yet -->
                        <div class="text-center p-4">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold text-amber-200 border border-amber-300/40 bg-black/30 backdrop-blur-xs mb-2">
                                {{ $badge }}
                            </span>
                            <div class="font-serif font-bold text-white text-lg tracking-wide drop-shadow-md">
                                {{ $template->name }}
                            </div>
                            <span class="text-[10px] text-amber-200/70 tracking-widest uppercase block mt-1">
                                No Custom Image Uploaded
                            </span>
                        </div>
                    @endif

                    <!-- Active Status Pill in Top Left -->
                    <div class="absolute top-2.5 left-2.5 z-10">
                        @if($template->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500 text-white shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> ACTIVE
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-700/80 backdrop-blur-xs text-slate-200">
                                INACTIVE
                            </span>
                        @endif
                    </div>

                    <!-- Occasion Tag in Top Right -->
                    <div class="absolute top-2.5 right-2.5 z-10">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 text-slate-800 shadow-xs uppercase tracking-wider backdrop-blur-xs">
                            {{ $template->occasion }}
                        </span>
                    </div>

                    <!-- Quick Image Upload Overlay Trigger -->
                    <button type="button"
                            onclick="openImageModal({{ $template->id }}, '{{ addslashes($template->name) }}')"
                            title="Upload or Change Card Image"
                            class="absolute bottom-2.5 right-2.5 z-10 p-2 rounded-xl bg-white/90 hover:bg-white text-slate-800 shadow-md backdrop-blur-xs transition-transform hover:scale-105">
                        <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                </div>

                <!-- Template Metadata & Styling Details -->
                <div class="p-4 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-bold text-sm text-slate-900 group-hover:text-rose-600 transition-colors">
                                {{ $template->name }}
                            </h3>
                            <span class="text-[11px] font-mono text-slate-400">#{{ $template->sort_order }}</span>
                        </div>
                        <div class="text-[11px] font-mono text-slate-400 mt-0.5 truncate">
                            {{ $template->slug }}
                        </div>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed">
                            {{ $template->description ?: 'No description provided.' }}
                        </p>

                        <!-- Swatches & Features Row -->
                        <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-1.5" title="Theme Colors: Primary / Accent / Card">
                                <span class="w-4 h-4 rounded-full border border-black/10 shadow-2xs" style="background-color: {{ $primaryColor }};"></span>
                                <span class="w-4 h-4 rounded-full border border-black/10 shadow-2xs" style="background-color: {{ $accentColor }};"></span>
                                <span class="w-4 h-4 rounded-full border border-black/10 shadow-2xs" style="background-color: {{ $cardBg }};"></span>
                                <span class="text-[10px] text-slate-400 font-medium ml-1 capitalize">{{ $cfg['borderStyle'] ?? 'double' }}</span>
                            </div>

                            <div class="text-[11px] font-medium text-slate-500">
                                {{ $template->cards_count }} cards
                            </div>
                        </div>
                    </div>

                    <!-- Actions Toolbar -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <!-- Toggle Active Form -->
                        <form action="{{ route('admin.greeting-templates.toggle-active', $template) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border transition-colors {{ $template->is_active ? 'border-slate-200 text-slate-600 hover:bg-slate-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                {{ $template->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>

                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.greeting-templates.edit', $template) }}"
                               class="text-xs font-bold px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition-colors inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                <span>Edit</span>
                            </a>

                            <form action="{{ route('admin.greeting-templates.destroy', $template) }}"
                                  method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete template \'{{ addslashes($template->name) }}\'? This will also delete cards created with this template.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition-colors" title="Delete template">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-12 rounded-2xl border border-slate-200 text-center shadow-xs">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">No greeting card designs found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Try adjusting your occasion filter or search keywords, or upload a brand new greeting card design.
                </p>
                <div class="mt-4">
                    <a href="{{ route('admin.greeting-templates.create') }}" class="px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition-colors inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Upload First Design
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $templates->links() }}
    </div>
</div>

<!-- Modal for Quick Image Upload -->
<div id="image-upload-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-base text-slate-900">Upload Greeting Card Image</h3>
                <p id="modal-template-name" class="text-xs text-slate-500 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeImageModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="modal-upload-form" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Select Image File</label>
                <div class="border-2 border-dashed border-slate-300 hover:border-rose-500 rounded-xl p-4 text-center cursor-pointer transition-colors" onclick="document.getElementById('modal-file-input').click()">
                    <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <div id="modal-preview-text" class="text-xs font-semibold text-slate-600">Click to choose image (PNG, JPG, WebP, GIF, SVG)</div>
                    <div class="text-[10px] text-slate-400 mt-1">Maximum size 5 MB</div>
                    <input type="file" id="modal-file-input" name="image" accept="image/*" class="hidden" required onchange="previewModalImage(this)">
                </div>

                <div id="modal-img-preview-box" class="mt-3 hidden rounded-xl overflow-hidden border border-slate-200 aspect-video relative">
                    <img id="modal-img-preview" src="#" alt="Preview" class="w-full h-full object-cover">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeImageModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition-colors">
                    Save Image
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openImageModal(templateId, templateName) {
    const form = document.getElementById('modal-upload-form');
    form.action = `/admin/greeting-templates/${templateId}/upload-image`;
    document.getElementById('modal-template-name').textContent = templateName;
    document.getElementById('modal-img-preview-box').classList.add('hidden');
    document.getElementById('modal-file-input').value = '';
    document.getElementById('modal-preview-text').textContent = 'Click to choose image (PNG, JPG, WebP, GIF, SVG)';
    const modal = document.getElementById('image-upload-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeImageModal() {
    const modal = document.getElementById('image-upload-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function previewModalImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('modal-preview-text').textContent = file.name;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('modal-img-preview').src = e.target.result;
            document.getElementById('modal-img-preview-box').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}
</script>
@endsection

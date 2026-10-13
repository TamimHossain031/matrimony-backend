@extends('admin.layouts.admin')

@section('title', 'Create Greeting Card Design')
@section('page_title', 'Upload & Create Greeting Card Design')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('admin.greeting-templates.index') }}" class="hover:text-rose-600 transition-colors">Greeting Designs</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">New Card Design</span>
        </div>

        <a href="{{ route('admin.greeting-templates.index') }}" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Designs</span>
        </a>
    </div>

    <form action="{{ route('admin.greeting-templates.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Controls & Upload Inputs (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Section 1: Template Basics -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-black">1</span>
                        Design Identity & Occasion
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                                Card Design Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   placeholder="e.g. Royal Emerald Vows"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50"
                                   oninput="updatePreviewTitle(this.value)">
                            @error('name') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="occasion" class="block text-xs font-bold text-slate-700 mb-1">
                                Occasion Category <span class="text-rose-500">*</span>
                            </label>
                            <select id="occasion"
                                    name="occasion"
                                    required
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50 font-medium"
                                    onchange="updateOccasionBadge(this.value)">
                                @foreach($occasions as $key => $label)
                                    <option value="{{ $key }}" {{ old('occasion') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('occasion') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="slug" class="block text-xs font-bold text-slate-700 mb-1">
                                URL Slug <span class="text-slate-400 font-normal">(Auto-generated if empty)</span>
                            </label>
                            <input type="text"
                                   id="slug"
                                   name="slug"
                                   value="{{ old('slug') }}"
                                   placeholder="e.g. royal-emerald-vows"
                                   class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50">
                            @error('slug') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="sort_order" class="block text-xs font-bold text-slate-700 mb-1">
                                Display Sort Order
                            </label>
                            <input type="number"
                                   id="sort_order"
                                   name="sort_order"
                                   value="{{ old('sort_order', 0) }}"
                                   min="0"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                            Aesthetic Description
                        </label>
                        <textarea id="description"
                                  name="description"
                                  rows="2"
                                  placeholder="Describe the card theme, mood, filigree details or styling highlights..."
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50">{{ old('description') }}</textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer">
                        <label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer select-none">
                            Active in Greeting Studio (Ready for users to choose)
                        </label>
                    </div>
                </div>

                <!-- Section 2: Upload Greeting Card Image / Artwork -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-black">2</span>
                            Upload Greeting Card Image / Artwork
                        </h3>
                        <span class="text-[11px] font-semibold text-slate-400">PNG, JPG, WebP, GIF, SVG</span>
                    </div>

                    <!-- Dropzone upload area -->
                    <div class="border-2 border-dashed border-slate-300 hover:border-rose-500 rounded-2xl p-6 text-center cursor-pointer transition-all bg-slate-50 hover:bg-rose-50/20"
                         onclick="document.getElementById('image-input').click()">
                        <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 mx-auto flex items-center justify-center mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div id="upload-box-caption" class="text-xs font-bold text-slate-800">
                            Click to select or drag and drop card artwork
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            High resolution greeting card artwork or template cover (Recommended: 800x600 px or square, max 5MB)
                        </p>
                        <input type="file"
                               id="image-input"
                               name="image"
                               accept="image/*"
                               class="hidden"
                               onchange="previewCardImage(this)">
                    </div>

                    <div id="image-preview-wrapper" class="hidden rounded-xl overflow-hidden border border-slate-200 relative aspect-video bg-slate-900">
                        <img id="image-preview-element" src="#" alt="Artwork Preview" class="w-full h-full object-cover">
                        <button type="button"
                                onclick="clearCardImage()"
                                class="absolute top-2 right-2 p-1.5 rounded-lg bg-slate-900/80 hover:bg-rose-600 text-white text-xs transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    @error('image') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Section 3: Visual Styling & Theme Palette -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-black">3</span>
                        Color Palette & Typography Styling
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="badge" class="block text-xs font-bold text-slate-700 mb-1">
                                Header Badge / Monogram
                            </label>
                            <input type="text"
                                   id="badge"
                                   name="badge"
                                   value="{{ old('badge', '💍 Holy Matrimony') }}"
                                   placeholder="e.g. 💍 Holy Matrimony"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50"
                                   oninput="updatePreviewBadge(this.value)">
                        </div>

                        <div>
                            <label for="icon" class="block text-xs font-bold text-slate-700 mb-1">
                                Divider Center Icon
                            </label>
                            <select id="icon"
                                    name="icon"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50 font-medium">
                                <option value="Sparkles" {{ old('icon') === 'Sparkles' ? 'selected' : '' }}>✨ Sparkles</option>
                                <option value="Heart" {{ old('icon') === 'Heart' ? 'selected' : '' }}>❤️ Romantic Heart</option>
                                <option value="Award" {{ old('icon') === 'Award' ? 'selected' : '' }}>🏆 Milestone Award</option>
                            </select>
                        </div>
                    </div>

                    <!-- Color Pickers Row -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label for="primaryColor" class="block text-[11px] font-bold text-slate-700 mb-1">
                                Primary (Headings)
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color"
                                       id="primaryColor"
                                       name="primaryColor"
                                       value="{{ old('primaryColor', '#c29b38') }}"
                                       class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0"
                                       oninput="syncColorInput(this.value, 'primaryColorHex'); updateLiveTheme();">
                                <input type="text"
                                       id="primaryColorHex"
                                       value="{{ old('primaryColor', '#c29b38') }}"
                                       class="w-full px-2 py-1.5 text-xs font-mono rounded-lg border border-slate-200 bg-slate-50"
                                       oninput="syncColorHex(this.value, 'primaryColor'); updateLiveTheme();">
                            </div>
                        </div>

                        <div>
                            <label for="accentColor" class="block text-[11px] font-bold text-slate-700 mb-1">
                                Accent (Borders & Foil)
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color"
                                       id="accentColor"
                                       name="accentColor"
                                       value="{{ old('accentColor', '#e5c06e') }}"
                                       class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0"
                                       oninput="syncColorInput(this.value, 'accentColorHex'); updateLiveTheme();">
                                <input type="text"
                                       id="accentColorHex"
                                       value="{{ old('accentColor', '#e5c06e') }}"
                                       class="w-full px-2 py-1.5 text-xs font-mono rounded-lg border border-slate-200 bg-slate-50"
                                       oninput="syncColorHex(this.value, 'accentColor'); updateLiveTheme();">
                            </div>
                        </div>

                        <div>
                            <label for="cardBg" class="block text-[11px] font-bold text-slate-700 mb-1">
                                Card Paper Background
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color"
                                       id="cardBg"
                                       name="cardBg"
                                       value="{{ old('cardBg', '#fcfaf5') }}"
                                       class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0"
                                       oninput="syncColorInput(this.value, 'cardBgHex'); updateLiveTheme();">
                                <input type="text"
                                       id="cardBgHex"
                                       value="{{ old('cardBg', '#fcfaf5') }}"
                                       class="w-full px-2 py-1.5 text-xs font-mono rounded-lg border border-slate-200 bg-slate-50"
                                       oninput="syncColorHex(this.value, 'cardBg'); updateLiveTheme();">
                            </div>
                        </div>

                        <div>
                            <label for="textColor" class="block text-[11px] font-bold text-slate-700 mb-1">
                                Message Text Color
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color"
                                       id="textColor"
                                       name="textColor"
                                       value="{{ old('textColor', '#2a241b') }}"
                                       class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0"
                                       oninput="syncColorInput(this.value, 'textColorHex'); updateLiveTheme();">
                                <input type="text"
                                       id="textColorHex"
                                       value="{{ old('textColor', '#2a241b') }}"
                                       class="w-full px-2 py-1.5 text-xs font-mono rounded-lg border border-slate-200 bg-slate-50"
                                       oninput="syncColorHex(this.value, 'textColor'); updateLiveTheme();">
                            </div>
                        </div>
                    </div>

                    <!-- Ambient Background Gradient -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="bgGradient" class="block text-xs font-bold text-slate-700">
                                Ambient Background Gradient
                            </label>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="setGradientPreset('champagne')" class="text-[10px] px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 font-medium">Champagne</button>
                                <button type="button" onclick="setGradientPreset('emerald')" class="text-[10px] px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 font-medium">Emerald</button>
                                <button type="button" onclick="setGradientPreset('crimson')" class="text-[10px] px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 font-medium">Crimson</button>
                                <button type="button" onclick="setGradientPreset('sapphire')" class="text-[10px] px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 font-medium">Sapphire</button>
                            </div>
                        </div>
                        <input type="text"
                               id="bgGradient"
                               name="bgGradient"
                               value="{{ old('bgGradient', 'linear-gradient(135deg, #1f1b14 0%, #2e2617 50%, #16120d 100%)') }}"
                               class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50"
                               oninput="updateLiveTheme()">
                    </div>

                    <!-- Typography & Ornaments -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="borderStyle" class="block text-xs font-bold text-slate-700 mb-1">
                                Border Filigree Style
                            </label>
                            <select id="borderStyle"
                                    name="borderStyle"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50 font-medium"
                                    onchange="updateLiveTheme()">
                                <option value="double" {{ old('borderStyle') === 'double' ? 'selected' : '' }}>Double Gold Foil</option>
                                <option value="ornate" {{ old('borderStyle') === 'ornate' ? 'selected' : '' }}>Ornate Vintage</option>
                                <option value="floral" {{ old('borderStyle') === 'floral' ? 'selected' : '' }}>Floral Botanical</option>
                                <option value="arabesque" {{ old('borderStyle') === 'arabesque' ? 'selected' : '' }}>Arabesque Arch</option>
                                <option value="solid" {{ old('borderStyle') === 'solid' ? 'selected' : '' }}>Modern Solid Minimal</option>
                            </select>
                        </div>

                        <div>
                            <label for="fontFamily" class="block text-xs font-bold text-slate-700 mb-1">
                                Default Typography
                            </label>
                            <select id="fontFamily"
                                    name="fontFamily"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50 font-medium"
                                    onchange="updateLiveTheme()">
                                <option value="serif">Playfair Luxe (Serif)</option>
                                <option value="cinzel">Cinzel Imperial</option>
                                <option value="cinzel-dec">Cinzel Decorative</option>
                                <option value="cormorant">Cormorant Garamond</option>
                                <option value="calligraphy">Great Vibes Calligraphy</option>
                                <option value="script">Dancing Script</option>
                                <option value="alex-brush">Alex Brush Signature</option>
                                <option value="handwriting">Caveat Handwritten</option>
                                <option value="montserrat">Montserrat Sans</option>
                                <option value="sans">Inter Modern Clean</option>
                                <option value="traditional">Tiro Bangla বাংলা</option>
                            </select>
                        </div>

                        <div>
                            <label for="particleType" class="block text-xs font-bold text-slate-700 mb-1">
                                Particle Celebration
                            </label>
                            <select id="particleType"
                                    name="particleType"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50 font-medium">
                                <option value="golden_stars">✨ Golden Stars</option>
                                <option value="rose_petals">🌹 Rose Petals</option>
                                <option value="sparkles">✨ Diamond Sparkles</option>
                                <option value="confetti">🎉 Party Confetti</option>
                                <option value="floating_hearts">💖 Floating Hearts</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.greeting-templates.index') }}"
                       class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md transition-colors inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Publish Greeting Card Design</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Interactive Real-Time Live Preview (5 cols) -->
            <div class="lg:col-span-5">
                <div class="sticky top-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black tracking-wider text-slate-400 uppercase">
                            Real-Time Card Preview
                        </h4>
                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                            Live Synced
                        </span>
                    </div>

                    <!-- Background wrapper mimicking user screen -->
                    <div id="preview-outer-wrap"
                         class="p-6 rounded-3xl shadow-xl flex items-center justify-center transition-all duration-300 min-h-[500px]"
                         style="background: linear-gradient(135deg, #1f1b14 0%, #2e2617 50%, #16120d 100%);">

                        <!-- Greeting Card Frame -->
                        <div id="preview-card-frame"
                             class="w-full max-w-sm rounded-2xl p-6 text-center shadow-2xl relative transition-all duration-200"
                             style="background-color: #fcfaf5; color: #2a241b; border: 3px double #e5c06e; outline: 1px solid rgba(255,255,255,0.4); outline-offset: -6px;">

                            <!-- Uploaded Artwork Banner (if uploaded) -->
                            <div id="card-art-preview-slot" class="hidden mb-4 rounded-xl overflow-hidden border border-black/10 aspect-video shadow-xs">
                                <img id="card-art-preview-img" src="#" alt="Card Artwork" class="w-full h-full object-cover">
                            </div>

                            <!-- Ornate Badge -->
                            <div class="mb-3">
                                <span id="preview-badge-label"
                                      class="inline-block px-3 py-1 rounded-full text-[11px] font-bold border transition-colors"
                                      style="border-color: #e5c06e; background-color: #e5c06e18; color: #c29b38;">
                                    💍 Holy Matrimony
                                </span>
                            </div>

                            <!-- Especially for -->
                            <div class="text-[10px] font-semibold tracking-wider text-slate-500 uppercase mb-1">
                                Especially for
                            </div>

                            <!-- Recipient Heading -->
                            <h2 id="preview-card-recipient"
                                class="text-2xl font-bold font-serif transition-colors mb-2"
                                style="color: #c29b38;">
                                Dear One
                            </h2>

                            <!-- Divider with Icon -->
                            <div class="flex items-center justify-center gap-2 my-3" style="color: #e5c06e;">
                                <div class="w-12 h-px bg-current opacity-40"></div>
                                <span id="preview-card-icon" class="text-xs">✨</span>
                                <div class="w-12 h-px bg-current opacity-40"></div>
                            </div>

                            <!-- Sample Message -->
                            <p id="preview-card-message"
                               class="text-xs italic leading-relaxed px-2 my-4"
                               style="color: #2a241b;">
                                Wishing you endless joy, peace, and blessings on this momentous occasion.
                            </p>

                            <!-- Stamp Footer -->
                            <div class="pt-4 border-t border-black/5 flex items-center justify-center gap-1.5 text-[10px]" style="color: #e5c06e;">
                                <span style="color: #c29b38;">♥</span>
                                <span class="font-semibold uppercase tracking-wider">Shondhan Celebrations</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400 text-center">
                        This preview renders exactly as users will see the card when opening it.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function syncColorInput(val, hexId) {
    document.getElementById(hexId).value = val;
}

function syncColorHex(val, inputId) {
    if (/^#[0-9A-F]{6}$/i.test(val)) {
        document.getElementById(inputId).value = val;
    }
}

function updatePreviewTitle(val) {
    const slugInput = document.getElementById('slug');
    if (!slugInput.dataset.manual) {
        slugInput.value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
}

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.manual = 'true';
});

function updateOccasionBadge(val) {
    const badges = {
        wedding: '💍 Holy Matrimony',
        anniversary: '🥂 Milestone Year',
        birthday: '🎂 Happy Birthday',
        eid: '🌙 Eid Mubarak',
        congratulations: '🎖️ Warmest Congratulations',
        thankyou: '🌿 Deepest Thanks',
        love: '💌 Forever Yours'
    };
    if (badges[val]) {
        document.getElementById('badge').value = badges[val];
        document.getElementById('preview-badge-label').textContent = badges[val];
    }
}

function updatePreviewBadge(val) {
    document.getElementById('preview-badge-label').textContent = val || '✨ Special Greeting';
}

function previewCardImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('upload-box-caption').textContent = file.name;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview-element').src = e.target.result;
            document.getElementById('image-preview-wrapper').classList.remove('hidden');

            // Also show in live card preview
            const cardImg = document.getElementById('card-art-preview-img');
            cardImg.src = e.target.result;
            document.getElementById('card-art-preview-slot').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function clearCardImage() {
    document.getElementById('image-input').value = '';
    document.getElementById('upload-box-caption').textContent = 'Click to select or drag and drop card artwork';
    document.getElementById('image-preview-wrapper').classList.add('hidden');
    document.getElementById('card-art-preview-slot').classList.add('hidden');
}

function setGradientPreset(preset) {
    const presets = {
        champagne: 'linear-gradient(135deg, #1f1b14 0%, #2e2617 50%, #16120d 100%)',
        emerald: 'linear-gradient(135deg, #0f241f 0%, #193830 50%, #0d1e1a 100%)',
        crimson: 'linear-gradient(135deg, #2b1219 0%, #3d1b24 50%, #1f0b12 100%)',
        sapphire: 'linear-gradient(135deg, #0b1a2e 0%, #122847 50%, #081321 100%)'
    };
    if (presets[preset]) {
        document.getElementById('bgGradient').value = presets[preset];
        updateLiveTheme();
    }
}

function updateLiveTheme() {
    const primary = document.getElementById('primaryColor').value;
    const accent = document.getElementById('accentColor').value;
    const cardBg = document.getElementById('cardBg').value;
    const textColor = document.getElementById('textColor').value;
    const bgGradient = document.getElementById('bgGradient').value;
    const borderStyle = document.getElementById('borderStyle').value;

    document.getElementById('preview-outer-wrap').style.background = bgGradient;

    const frame = document.getElementById('preview-card-frame');
    frame.style.backgroundColor = cardBg;
    frame.style.color = textColor;

    if (borderStyle === 'double') {
        frame.style.border = `4px double ${accent}`;
        frame.style.outline = '1px solid rgba(255, 255, 255, 0.4)';
    } else if (borderStyle === 'ornate') {
        frame.style.border = `3px solid ${accent}`;
        frame.style.outline = `1px dashed ${accent}88`;
    } else if (borderStyle === 'floral') {
        frame.style.border = `2px solid ${accent}`;
        frame.style.outline = `2px solid ${accent}44`;
    } else if (borderStyle === 'arabesque') {
        frame.style.border = `3px solid ${accent}`;
        frame.style.outline = `2px double ${accent}99`;
    } else {
        frame.style.border = `2px solid ${accent}`;
        frame.style.outline = 'none';
    }

    const badge = document.getElementById('preview-badge-label');
    badge.style.borderColor = accent;
    badge.style.backgroundColor = `${accent}18`;
    badge.style.color = primary;

    document.getElementById('preview-card-recipient').style.color = primary;
    document.getElementById('preview-card-message').style.color = textColor;
}
</script>
@endsection

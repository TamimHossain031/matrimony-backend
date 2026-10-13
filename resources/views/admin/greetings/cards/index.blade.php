@extends('admin.layouts.admin')

@section('title', 'User Sent Greeting Cards')
@section('page_title', 'Sent Greeting Cards Moderation')

@section('content')
<div class="space-y-6">
    <!-- Top Sub-Tabs & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
            <a href="{{ route('admin.greeting-templates.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                Designs & Templates
            </a>
            <a href="{{ route('admin.greeting-cards.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-rose-600 text-white shadow-xs">
                User Sent Cards ({{ $stats['total'] }})
            </a>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">Total Cards</div>
            <div class="text-xl font-black text-slate-900 mt-0.5">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">Published</div>
            <div class="text-xl font-black text-emerald-600 mt-0.5">{{ $stats['published'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">Disabled</div>
            <div class="text-xl font-black text-amber-600 mt-0.5">{{ $stats['disabled'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">Total Views</div>
            <div class="text-xl font-black text-blue-600 mt-0.5">{{ $stats['views'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">Total Shares</div>
            <div class="text-xl font-black text-purple-600 mt-0.5">{{ $stats['shares'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">Total Reactions</div>
            <div class="text-xl font-black text-rose-600 mt-0.5">{{ $stats['reactions'] }}</div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.greeting-cards.index') }}" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-80">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search recipient, sender, ID..."
                       class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <select name="occasion" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 py-1.5 px-3 bg-slate-50 font-medium text-slate-700">
                    <option value="all" {{ $currentOccasion === 'all' ? 'selected' : '' }}>All Occasions</option>
                    @foreach($occasions as $key => $label)
                        <option value="{{ $key }}" {{ $currentOccasion === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 py-1.5 px-3 bg-slate-50 font-medium text-slate-700">
                    <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="published" {{ $currentStatus === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="disabled" {{ $currentStatus === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>

                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Cards Moderation Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Card / ID</th>
                        <th class="py-3 px-4">Template & Image</th>
                        <th class="py-3 px-4">To / From</th>
                        <th class="py-3 px-4">Message Snippet</th>
                        <th class="py-3 px-4">Engagement</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($cards as $card)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-mono text-slate-900 font-bold text-xs">{{ $card->public_id }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $card->created_at->format('M d, Y h:i A') }}</div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-lg overflow-hidden border border-slate-200 shrink-0 bg-slate-900 flex items-center justify-center">
                                        @if($card->template?->thumbnail_url)
                                            <img src="{{ $card->template->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xs">✨</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $card->template?->name ?? 'Custom' }}</div>
                                        <div class="text-[10px] text-slate-400 uppercase">{{ $card->occasion }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $card->recipient_name }}</div>
                                <div class="text-[10px] text-slate-500">From: {{ $card->sender_name ?: 'Anonymous' }}</div>
                            </td>

                            <td class="py-3 px-4 max-w-xs">
                                <div class="text-slate-600 line-clamp-2 leading-relaxed">
                                    {{ $card->message }}
                                </div>
                            </td>

                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2 text-[11px]">
                                    <span title="Views" class="text-blue-600 font-semibold">{{ $card->views_count }}v</span>
                                    <span>&bull;</span>
                                    <span title="Shares" class="text-purple-600 font-semibold">{{ $card->shares_count }}s</span>
                                    <span>&bull;</span>
                                    <span title="Reactions" class="text-rose-600 font-semibold">{{ $card->reactions_count }}r</span>
                                </div>
                            </td>

                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($card->status === 'published')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        PUBLISHED
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                        DISABLED
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Toggle Status Form -->
                                    <form action="{{ route('admin.greeting-cards.status', $card) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $card->status === 'published' ? 'disabled' : 'published' }}">
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-colors {{ $card->status === 'published' ? 'border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                            {{ $card->status === 'published' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>

                                    <!-- Delete Card Form -->
                                    <form action="{{ route('admin.greeting-cards.destroy', $card) }}"
                                          method="POST"
                                          onsubmit="return confirm('Delete this greeting card permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition-colors" title="Delete card">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 text-xs">
                                No greeting cards matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cards->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $cards->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

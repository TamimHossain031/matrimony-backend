@extends('admin.layouts.admin')

@section('title', 'Photo Moderation')
@section('page_title', 'Photo Moderation & Visual Approval (§12)')

@section('content')
<div class="space-y-6">
    <!-- Status Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0">
        <a href="{{ route('admin.photos.index', ['status' => 'pending']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $status === 'pending' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Pending Review ({{ $statusCounts['pending'] }})
        </a>
        <a href="{{ route('admin.photos.index', ['status' => 'approved']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $status === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Approved ({{ $statusCounts['approved'] }})
        </a>
        <a href="{{ route('admin.photos.index', ['status' => 'rejected']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $status === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Rejected ({{ $statusCounts['rejected'] }})
        </a>
        <a href="{{ route('admin.photos.index', ['status' => 'all']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $status === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            All Photos ({{ $statusCounts['all'] }})
        </a>
    </div>

    <!-- Batch Action Form Wrap -->
    <form id="batch-form" action="{{ route('admin.photos.batch') }}" method="POST">
        @csrf
        <!-- Batch Action Toolbar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer" onclick="toggleAll(this)">
                <label for="select-all" class="text-xs font-bold text-slate-700 cursor-pointer select-none">Select All On This Page</label>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" name="action" value="approve" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-colors">
                    Batch Approve
                </button>
                <button type="submit" name="action" value="reject" class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs shadow-xs transition-colors">
                    Batch Reject
                </button>
            </div>
        </div>

        <!-- Photos Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @forelse($photos as $photo)
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs flex flex-col justify-between group hover:border-slate-300 transition-all">
                    <div>
                        <!-- Header / Selection -->
                        <div class="p-2.5 flex items-center justify-between bg-slate-50 border-b border-slate-100">
                            <input type="checkbox" name="photo_ids[]" value="{{ $photo->id }}" class="photo-checkbox rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer">
                            <div class="flex items-center gap-1">
                                @if($photo->is_primary)
                                    <span class="bg-amber-500 text-slate-900 text-[9px] font-black px-1.5 py-0.5 rounded">PRIMARY</span>
                                @endif
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $photo->moderation_status === \App\Enums\PhotoModerationStatus::Approved ? 'bg-emerald-100 text-emerald-800' : ($photo->moderation_status === \App\Enums\PhotoModerationStatus::Pending ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ strtoupper($photo->moderation_status->value) }}
                                </span>
                            </div>
                        </div>

                        <!-- Image Preview -->
                        <div class="aspect-square bg-slate-100 relative overflow-hidden">
                            <img src="{{ route('admin.photos.preview', $photo) }}" alt="Candidate Photo" class="w-full h-full object-cover">
                            @if($photo->blurred_path)
                                <a href="{{ route('admin.photos.preview-blurred', $photo) }}" target="_blank" class="absolute bottom-1 right-1 bg-slate-900/80 hover:bg-slate-900 text-white text-[9px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                    View Blurred
                                </a>
                            @endif
                        </div>

                        <!-- Info -->
                        <div class="p-3 text-xs">
                            <div class="font-bold text-slate-900 truncate">{{ $photo->profile?->full_name_en ?? 'No name' }}</div>
                            <div class="text-[11px] font-mono text-rose-600 truncate">{{ $photo->profile?->public_id ?? 'No ID' }}</div>
                            <div class="text-[10px] text-slate-400 mt-1">{{ $photo->width }}x{{ $photo->height }} px &bull; {{ $photo->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-1">
                        <div class="flex gap-1">
                            @if($photo->moderation_status !== \App\Enums\PhotoModerationStatus::Approved)
                                <button type="submit" form="single-approve-{{ $photo->id }}" class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold">
                                    Approve
                                </button>
                            @endif
                            @if($photo->moderation_status !== \App\Enums\PhotoModerationStatus::Rejected)
                                <button type="submit" form="single-reject-{{ $photo->id }}" class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold">
                                    Reject
                                </button>
                            @endif
                        </div>
                        <button type="submit" form="single-delete-{{ $photo->id }}" onclick="return confirm('Delete photo?')" class="p-1 text-slate-400 hover:text-rose-600 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center text-slate-400 text-sm bg-white rounded-2xl border border-slate-200">
                    No photos found in this category.
                </div>
            @endforelse
        </div>
    </form>

    <!-- Hidden Individual Action Forms -->
    @foreach($photos as $photo)
        <form id="single-approve-{{ $photo->id }}" action="{{ route('admin.photos.approve', $photo) }}" method="POST" class="hidden">@csrf</form>
        <form id="single-reject-{{ $photo->id }}" action="{{ route('admin.photos.reject', $photo) }}" method="POST" class="hidden">@csrf</form>
        <form id="single-delete-{{ $photo->id }}" action="{{ route('admin.photos.destroy', $photo) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
    @endforeach

    @if($photos->hasPages())
        <div class="p-4 bg-white rounded-2xl border border-slate-200">
            {{ $photos->links() }}
        </div>
    @endif
</div>

<script>
function toggleAll(master) {
    document.querySelectorAll('.photo-checkbox').forEach(cb => {
        cb.checked = master.checked;
    });
}
</script>
@endsection

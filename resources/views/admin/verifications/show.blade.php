@extends('admin.layouts.admin')

@section('title', 'Verification Request #' . $verification->id)
@section('page_title', 'Identity Verification Review #' . $verification->id)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            @php $targetLevel = is_object($verification->level) ? $verification->level->value : (int) $verification->level; @endphp
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 uppercase">
                    Claiming Level {{ $targetLevel }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $verification->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($verification->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') }}">
                    {{ $verification->status }}
                </span>
            </div>
            <div class="mt-2 text-xs text-slate-400">Submitted on {{ $verification->created_at->format('F d, Y \a\t H:i') }}</div>
        </div>

        @if($verification->reviewed_at)
            <div class="text-right text-xs text-slate-500">
                Reviewed on: <strong class="text-slate-800">{{ $verification->reviewed_at->format('M d, Y H:i') }}</strong><br>
                by: <strong class="text-slate-800">{{ $verification->reviewer?->name ?? 'Staff' }}</strong>
            </div>
        @endif
    </div>

    <!-- Candidate Card & Document Preview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Candidate Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <h3 class="font-bold text-slate-900 text-base mb-4 border-b border-slate-100 pb-2">Candidate Details</h3>
            @if($verification->user)
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-700 font-extrabold text-xl flex items-center justify-center border border-blue-200">
                        {{ strtoupper(substr($verification->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">{{ $verification->user->name }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $verification->user->email }} &bull; {{ $verification->user->phone }}</div>
                        <div class="mt-1 text-xs">
                            Current Status: <strong class="text-slate-800 capitalize">{{ $verification->user->status->value }}</strong>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-xs space-y-1.5">
                    <div>Current Profile Verification: <strong class="text-blue-700">Level {{ $verification->user->profile?->verification_level ?? 0 }}</strong></div>
                    <div>Public Profile ID: <strong class="font-mono text-rose-600">{{ $verification->user->profile?->public_id ?? 'None' }}</strong></div>
                    <div>Account Age: <strong>{{ $verification->user->created_at->diffForHumans() }}</strong></div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('admin.users.show', $verification->user) }}" class="text-xs font-bold text-slate-900 hover:text-rose-600">
                        View Complete User Profile &rarr;
                    </a>
                </div>
            @else
                <div class="text-slate-400 text-sm">User record no longer exists.</div>
            @endif
        </div>

        <!-- Document Preview / File -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <h3 class="font-bold text-slate-900 text-base mb-4 border-b border-slate-100 pb-2">Submitted Proof / Document</h3>
            @if($verification->document_path)
                <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-3">
                    <svg class="w-12 h-12 text-slate-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div class="text-xs font-mono text-slate-600 truncate">{{ basename($verification->document_path) }}</div>
                    <a href="{{ route('admin.verifications.document', $verification) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-xs">
                        Download / View Document
                    </a>
                </div>
            @else
                <div class="p-8 bg-slate-50 rounded-xl text-center text-slate-400 text-sm">
                    No physical document uploaded (standard for Level 1 phone/email confirmation).
                </div>
            @endif
        </div>
    </div>

    <!-- Review Decision Forms -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-6">
        <h3 class="font-bold text-slate-900 text-base">Verification Decision</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Approve Form -->
            <form action="{{ route('admin.verifications.approve', $verification) }}" method="POST" class="p-5 rounded-xl border border-emerald-200 bg-emerald-50/40 space-y-4">
                @csrf
                <h4 class="font-bold text-emerald-900 text-sm">Approve Claim to Level {{ $targetLevel }}</h4>
                <p class="text-xs text-emerald-700">Approving will grant Level {{ $targetLevel }} badge on candidate's profile.</p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Approval Note (Optional)</label>
                    <input type="text"
                           name="review_note"
                           value="{{ $verification->status === 'approved' ? $verification->review_note : 'Verified by staff review' }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs">
                    Approve Verification
                </button>
            </form>

            <!-- Reject Form -->
            <form action="{{ route('admin.verifications.reject', $verification) }}" method="POST" class="p-5 rounded-xl border border-rose-200 bg-rose-50/40 space-y-4">
                @csrf
                <h4 class="font-bold text-rose-900 text-sm">Reject Claim</h4>
                <p class="text-xs text-rose-700">Requires a rejection note explaining why proof was insufficient.</p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Rejection Reason</label>
                    <input type="text"
                           name="review_note"
                           value="{{ $verification->status === 'rejected' ? $verification->review_note : '' }}"
                           required
                           placeholder="Document unreadable / Selfie does not match NID..."
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>

                <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs">
                    Reject Verification
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

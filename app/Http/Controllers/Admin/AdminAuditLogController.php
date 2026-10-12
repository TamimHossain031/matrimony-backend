<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('actor')->latest('id');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->input('actor_id'));
        }

        $logs = $query->paginate(25)->withQueryString();

        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $staffMembers = User::whereIn('role', ['admin', 'moderator'])->get(['id', 'name']);

        return view('admin.audit-logs.index', compact('logs', 'actions', 'staffMembers'));
    }
}

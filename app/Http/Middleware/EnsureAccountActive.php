<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks deleted accounts and restricts most actions for deactivated ones.
 * Login is handled separately; this guards authenticated API actions.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === AccountStatus::Deleted) {
            return response()->json(['message' => 'This account has been deleted.'], 403);
        }

        return $next($request);
    }
}

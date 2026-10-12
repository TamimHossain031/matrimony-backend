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

        if ($user && ($user->status === AccountStatus::Deleted || $user->status === AccountStatus::Suspended)) {
            $msg = $user->status === AccountStatus::Suspended
                ? 'This account has been suspended.'
                : 'This account has been deleted.';

            return response()->json(['message' => $msg], 403);
        }

        return $next($request);
    }
}

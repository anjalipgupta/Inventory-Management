<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $closure, ...$roles)
    {
        $user = $request->user();
        
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return $closure($request);
    }
}
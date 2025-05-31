<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HallOwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'hall_owner') {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized. Hall Owners only.'], 403);
    }
}
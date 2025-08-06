<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CoordinatorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'coordinator') { 
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized. Coordinator only.'], 403);
    }
}
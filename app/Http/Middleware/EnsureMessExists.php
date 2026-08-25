<?php

namespace App\Http\Middleware;

use App\Models\Mess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMessExists
{
    /**
     * Every mess-scoped page needs a resolved tenant. A user who has not yet
     * joined (with a code) or created a mess has Mess::activeId() === null and
     * is sent to the chooser instead of seeing an empty — or worse, someone
     * else's — mess.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip on the chooser / onboarding routes themselves to avoid loops.
        if ($request->routeIs('onboarding.*', 'join.*')) {
            return $next($request);
        }

        if (Mess::activeId() === null) {
            return redirect()->route('join.choose');
        }

        return $next($request);
    }
}

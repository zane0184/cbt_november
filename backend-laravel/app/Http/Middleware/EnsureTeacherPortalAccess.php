<?php

namespace App\Http\Middleware;

use App\Support\Security\TeacherPortalAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherPortalAccess
{
    public function __construct(private readonly TeacherPortalAccess $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->access->profile($request->user());

        return $next($request);
    }
}

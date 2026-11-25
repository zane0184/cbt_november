<?php

namespace App\Http\Middleware;

use App\Services\InstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventReinstallation
{
    protected InstallerService $installerService;

    public function __construct(InstallerService $installerService)
    {
        $this->installerService = $installerService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installerService->isInstalled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aplikasi CBT Madrasah sudah terinstal. Akses installer ditolak.',
                ], 403);
            }

            return redirect('/login')->with('info', 'Aplikasi CBT Madrasah sudah terinstal.');
        }

        return $next($request);
    }
}

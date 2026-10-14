<?php

namespace App\Http\Middleware;

use App\Support\PublicDemoGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class ProtectPublicDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('demo')) {
            return $next($request);
        }

        PublicDemoGuard::assertIsolated();

        if ($request->isMethodSafe()) {
            return $next($request);
        }

        // Public showcase: real asset create/update, no arbitrary uploads, identity changes, or data imports.
        if (count($request->allFiles()) > 0
            || $request->is('forgot-password', 'reset-password', 'imports', 'imports/*',
                'interoperability', 'interoperability/*', 'administration', 'administration/*',
                'assets/labels/prepare', 'assets/*/documents', 'assets/*/qr')) {
            abort(403, 'Fitur ini tidak tersedia pada demo publik.');
        }

        $key = 'demo-write:'.hash('sha256', (string) $request->ip());

        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429, 'Terlalu banyak permintaan demo. Coba lagi sebentar.');
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}

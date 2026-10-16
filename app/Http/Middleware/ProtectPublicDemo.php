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

        // Public demo omits sensitive read/download/admin surfaces.
        if ($request->is('documents/*/download', 'imports', 'imports/*',
            'interoperability', 'interoperability/*', 'administration',
            'administration/*', 'qr/*', 'verifikasi-aset/*',
            'forgot-password', 'reset-password', 'reset-password/*')) {
            abort(403, 'Fitur ini tidak tersedia pada demo publik.');
        }

        if ($request->isMethodSafe()) {
            return $next($request);
        }

        if (count($request->allFiles()) > 0) {
            abort(403, 'Unggah file dinonaktifkan pada demo publik.');
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $allowed = $request->isMethod('POST')
            ? in_array($routeName, ['login.store', 'logout', 'assets.store', 'tenant.switch'], true)
            : $request->isMethod('PATCH') && $routeName === 'assets.update';

        if (! $allowed) {
            abort(403, 'Perubahan ini tidak diizinkan pada demo publik.');
        }

        $key = 'demo-writes:'.hash('sha256', (string) $request->ip());

        if (RateLimiter::tooManyAttempts($key, 40)) {
            abort(429, 'Terlalu banyak permintaan ke demo. Coba lagi nanti.');
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}

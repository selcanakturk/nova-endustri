<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $privatePage = $request->is('admin', 'admin/*', 'urunler/*/teklif');
        $error = $response->getStatusCode() >= 400;

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if ($privatePage || $error) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        if ($privatePage || $error || $request->is('iletisim')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}

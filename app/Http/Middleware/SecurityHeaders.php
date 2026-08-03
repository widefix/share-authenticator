<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $development = app()->isLocal();
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "media-src 'self' blob:",
            $development ? "font-src 'self' data: http:" : "font-src 'self' data:",
            $development ? "script-src 'self' 'unsafe-inline' http:" : "script-src 'self'",
            $development ? "style-src 'self' 'unsafe-inline' http:" : "style-src 'self'",
            $development ? "connect-src 'self' ws: http:" : "connect-src 'self'",
        ]));
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}

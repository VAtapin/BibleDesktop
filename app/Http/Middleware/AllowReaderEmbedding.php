<?php

/**
 * BibleDesktop - Bible study desktop and web application.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 *
 * @link https://bible-desktop.com/
 *
 * @copyright 2026 Atapin Vladimir / Bible Media
 *
 * @version 1.0.0
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows the public reader to be embedded by the supported mini-app hosts.
 */
class AllowReaderEmbedding
{
    /**
     * Add frame policy headers to a public reader response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Frame-Options');
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self' https://vk.com https://*.vk.com https://vk.ru https://*.vk.ru https://ok.ru https://*.ok.ru https://web.telegram.org https://*.telegram.org"
        );

        return $response;
    }
}

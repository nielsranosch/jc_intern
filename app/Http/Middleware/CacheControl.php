<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;

class CacheControl
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (!$response->headers->get('expires')) {
            $response->header('cache-control', 'no-store,no-cache,must-revalidate');
            $response->header('expires', '-1');
        }

        return $response;
    }
}

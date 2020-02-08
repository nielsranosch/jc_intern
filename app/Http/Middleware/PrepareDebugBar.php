<?php

namespace App\Http\Middleware;

use Closure;

class PrepareDebugBar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*
         * Debugbar will be enabled on boot to work when needed (debugbar.enabled is true!). However, virtually always we do not want it to be active.
         *
         * TODO: check if debugbar.enabled affects performance
         */
        if (!config('app.debug')) {
            \Debugbar::disable();
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;

class AppInstalled
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
      if(!$request->routeIs('home.install_app') && !config('app.installed'))
      {
        return redirect()->route('home.install_app');
      }

      return $next($request);
    }
}

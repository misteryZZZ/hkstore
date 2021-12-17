<?php

namespace App\Http\Middleware;

use Closure;

class IsBlocked
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
        if(($blocked_users = \Cache::get('blocked_users', [])) && \Auth::check())
        {
            if(in_array(\Auth::id(), array_values($blocked_users)))
            {
                \Auth::logout();
                
                return redirect('/');
            }
        }

        return $next($request);
    }
}

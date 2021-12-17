<?php

namespace App\Http\Middleware;

use Closure;

class AuthIfSubscription
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
        if(strtolower($request->query('type')) === 'subscription' && !\Auth::check())
        {
            return redirect()->route('login', ['redirect' => url()->full()]);
        }

        return $next($request);
    }
}

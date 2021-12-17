<?php

namespace App\Http\Middleware;

use Closure;

class IsNotAdmin
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
        $role = $request->user()->role ?? null;
        
        if($role !== 'admin')
            return $next($request);

        return redirect()->route('home');
    }
}

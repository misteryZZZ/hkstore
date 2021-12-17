<?php

namespace App\Http\Middleware;

use Closure;

class ValidateItemStatus
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
        if(preg_match('/^(active|trending|featured)$/i', $request->input('status')) && 
           ctype_digit($request->input('id')))
        {
            return $next($request);
        }
        
        abort(404);
    }
}

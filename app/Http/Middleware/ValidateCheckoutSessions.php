<?php

namespace App\Http\Middleware;

use Closure;

class ValidateCheckoutSessions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next)
    {        
      /*$request->session()->has(['products_ids', 'payment_processor', 'cart']) || \Cache::has($request->token) || \Cache::has($request->post('order_id')) || abort(404);*/

      return $next($request);
    }
}

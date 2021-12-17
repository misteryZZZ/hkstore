<?php

namespace App\Http\Middleware;

use Closure;

class ValidateCheckoutRequest
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
      $cart = json_decode($request->cart) ?? abort(404);

      $ids = array_column($cart, 'id') ?? abort(404);

      return $next($request);
    }
}

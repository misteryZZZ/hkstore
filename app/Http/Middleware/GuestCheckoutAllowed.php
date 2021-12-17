<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\{ Auth, Cache };


class GuestCheckoutAllowed
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
        if(!Auth::check() && !config('payments.guest_checkout') && !Cache::has("iyzico-{$request->token}") && 
		   !Cache::has("paymentwall-{$request->order_id}") && !Cache::has("payhere-{$request->order_id}"))
        {
            return redirect()->route('login');
        }

        return $next($request);
    }
}

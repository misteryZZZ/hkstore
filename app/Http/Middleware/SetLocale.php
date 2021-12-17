<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
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
        $locale = session('locale', config('app.locale'));

        if(in_array($locale, \LaravelLocalization::getSupportedLanguagesKeys()))
        {
            \LaravelLocalization::setLocale($locale);

            config(['translation' => json_decode(\File::get(resource_path("lang/{$locale}.json")), JSON_UNESCAPED_UNICODE)]);
        }

        return $next($request);
    }
}

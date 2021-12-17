<?php

namespace App\Http\Middleware;

use Closure;

class SetTemplate
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
        $template  = config('app.template', 'valexa');
        $template  = (auth_is_admin() || env_is('development')) ? session('template', $template) : $template;
        $templates = \File::glob(resource_path('views/front/*', GLOB_ONLYDIR));
        $base_path = resource_path('views/front/');
        $templates = str_ireplace($base_path, '', $templates);
        $templates = array_filter($templates, 'is_dir');

        if(in_array($template, $templates))
        {
          config(['app.template' => $template]);
          config(['app.top_cover' => config("app.{$template}_top_cover")]);
        }

        return $next($request);
    }
}